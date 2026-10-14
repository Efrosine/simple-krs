<?php

namespace App\Http\Controllers;

use App\Events\EnrollmentChanged;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()->role === 'student', 403);

        $validated = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
        ]);
        $key = $validated['idempotency_key'];

        $replayed = Enrollment::query()->where('idempotency_key', $key)->first();
        if ($replayed) {
            if ($replayed->user_id !== $request->user()->id || $replayed->course_id !== $course->id) {
                abort(409, 'Kunci permintaan sudah digunakan untuk operasi lain.');
            }

            return back()->with('status', 'Kelas sudah tercatat pada permintaan sebelumnya.');
        }

        try {
            DB::transaction(function () use ($request, $course, $key) {
                $lockedCourse = Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();

                if (Enrollment::query()
                    ->where('user_id', $request->user()->id)
                    ->where('course_id', $lockedCourse->id)
                    ->exists()) {
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

                Enrollment::create([
                    'user_id' => $request->user()->id,
                    'course_id' => $lockedCourse->id,
                    'idempotency_key' => $key,
                ]);
            }, attempts: 3);
        } catch (QueryException $exception) {
            $message = $exception->getMessage();
            $isUniqueViolation = in_array((string) $exception->getCode(), ['23000', '23505', '19'], true)
                || str_contains($message, 'UNIQUE constraint failed');

            if ($isUniqueViolation) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Kelas sudah diambil atau permintaan yang sama sudah diproses. Muat ulang halaman untuk melihat status terbaru.',
                ]);
            }

            throw $exception;
        }

        $this->broadcastCapacity($course);

        return back()->with('status', 'Berhasil mengambil kelas.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()->role === 'student', 403);

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

        $this->broadcastCapacity($course);

        return back()->with('status', 'Berhasil melepas kelas.');
    }

    private function broadcastCapacity(Course $course): void
    {
        broadcast(new EnrollmentChanged(
            courseId: $course->id,
            enrolledCount: Enrollment::where('course_id', $course->id)->count(),
            capacity: $course->capacity,
        ));
    }
}
