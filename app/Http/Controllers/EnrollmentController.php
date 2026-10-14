<?php

namespace App\Http\Controllers;

use App\Events\EnrollmentChanged;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $key = (string) Str::uuid();

        try {
            $enrollment = DB::transaction(function () use ($request, $course, $key) {
                $lockedCourse = Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();

                $existing = Enrollment::query()
                    ->where('user_id', $request->user()->id)
                    ->where('course_id', $lockedCourse->id)
                    ->first();

                if ($existing) {
                    throw ValidationException::withMessages([
                        'enrollment' => 'Kamu sudah mengambil kelas ini.',
                    ]);
                }

                $enrolledCount = Enrollment::query()
                    ->where('course_id', $lockedCourse->id)
                    ->count();

                if ($enrolledCount >= $lockedCourse->capacity) {
                    throw ValidationException::withMessages([
                        'enrollment' => 'Kuota kelas sudah penuh.',
                    ]);
                }

                return Enrollment::create([
                    'user_id' => $request->user()->id,
                    'course_id' => $lockedCourse->id,
                    'idempotency_key' => $key,
                ]);
            }, attempts: 3);
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'enrollments_user_id_course_id_unique')) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Kamu sudah mengambil kelas ini.',
                ]);
            }

            throw $exception;
        }

        broadcast(new EnrollmentChanged(
            courseId: $course->id,
            enrolledCount: Enrollment::where('course_id', $course->id)->count(),
            capacity: $course->capacity,
        ))->toOthers();

        return back()->with('status', 'Berhasil mengambil kelas.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($request, $course) {
            Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();

            return Enrollment::query()
                ->where('user_id', $request->user()->id)
                ->where('course_id', $course->id)
                ->delete();
        }, attempts: 3);

        if (! $deleted) {
            throw ValidationException::withMessages([
                'enrollment' => 'Kamu belum mengambil kelas ini.',
            ]);
        }

        broadcast(new EnrollmentChanged(
            courseId: $course->id,
            enrolledCount: Enrollment::where('course_id', $course->id)->count(),
            capacity: $course->capacity,
        ))->toOthers();

        return back()->with('status', 'Berhasil melepas kelas.');
    }
}
