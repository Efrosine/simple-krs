<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\View\View;

class ClassCatalogController extends Controller
{
    public function index(): View
    {
        $courses = Course::query()
            ->withCount('enrollments')
            ->with(['enrollments' => fn ($query) => $query->where('user_id', auth()->id())])
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->orderBy('course_code')
            ->get();

        return view('classes.index', compact('courses'));
    }
}
