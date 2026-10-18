<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ClassCatalogController extends Controller
{
    public function index(): View
    {
        try {
            // Catalog counts may be eventually consistent, so read them from the replica.
            $courses = $this->catalogQuery('pgsql_replica')->get();
        } catch (\Throwable $exception) {
            // A failed replica must not make the catalog unavailable.
            Log::warning('PostgreSQL replica unavailable; serving catalog from primary.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $courses = $this->catalogQuery('pgsql')->get();
        }

        // Read-your-writes: enrollment ownership and the student's credit total always come
        // from the primary, even if the replica has not caught up yet.
        $enrolledCourseIds = Enrollment::query()
            ->where('user_id', auth()->id())
            ->pluck('course_id')
            ->flip();

        foreach ($courses as $course) {
            $course->setAttribute('is_enrolled', $enrolledCourseIds->has($course->id));
        }

        $currentCredits = (int) $courses
            ->filter(fn ($course) => $course->is_enrolled)
            ->sum('credits');
        $maxCredits = (int) config('krs.max_credits', 24);

        return view('classes.index', compact('courses', 'currentCredits', 'maxCredits'));
    }

    private function catalogQuery(string $connection)
    {
        return Course::on($connection)
            ->withCount('enrollments')
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->orderBy('course_code');
    }
}
