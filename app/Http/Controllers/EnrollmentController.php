<?php

namespace App\\Http\\Controllers;

use App\\Events\\EnrollmentChanged;
use App\\Models\\Course;
use App\\Models\\Enrollment;
use App\\Models\\User;
use Illuminate\\Database\\QueryException;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;

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
                // Serialize KRS changes for the same student so concurrent requests
                // cannot bypass the SKS limit or schedule-conflict checks.
                User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
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

                $currentCourses = Course::query()
                    ->whereHas('enrollments', fn ($query) => $query->where('user_id', $request->user()->id))
                    ->get();

                $currentCredits = (int) $currentCourses->sum('credits');
                $maxCredits = (int) config('krs.max_credits', 24);

                if ($currentCredits + $lockedCourse->credits > $maxCredits) {
                    throw ValidationException::withMessages([
                        'enrollment' => "Batas maksimal KRS adalah {$maxCredits} SKS. Saat ini kamu mengambil {$currentCredits} SKS; kelas ini akan membuat total menjadi ".($currentCredits + $lockedCourse->credits).' SKS.',
                    ]);
                }

                foreach ($currentCourses as $currentCourse) {
                    if (
                        $currentCourse->day_of_week === $lockedCourse->day_of_week
                        && $currentCourse->starts_at < $lockedCourse->ends_at
                        && $currentCourse->ends_at > $lockedCourse->starts_at
                    ) {
                        throw ValidationException::withMessages([
                            'enrollment' => "Jadwal kelas {$lockedCourse->course_code} bentrok dengan {$currentCourse->course_code}.",
                        ]);
                    }
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
            // Keep the same lock order as store() to serialize this student's KRS changes.
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
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
