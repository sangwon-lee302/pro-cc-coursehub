<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Review;
use App\Support\LikeEscaper;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        // Get published courses for the listing page
        $query = Course::where('status', 'published');

        if ($request->filled('search')) {
            $search = LikeEscaper::escape($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->input('difficulty'));
        }

        $courses = $query->latest()->paginate(12);
        $categories = Category::all();

        return view('courses.index', compact('courses', 'categories'));
    }

    // Display course detail page with chapters and lessons
    public function show(Course $course)
    {
        $this->authorize('view', $course);

        // コース関連データを一括取得
        $course->load([
            'chapters.lessons',
            'user',
            'category',
            'tags',
            'reviews' => fn ($query) => $query->with('user')->latest(),
        ]);

        $enrollment = null;
        if (auth()->user()->isStudent()) {
            $enrollment = $course->enrollments()
                ->where('user_id', auth()->id())
                ->first();
        }

        $reviews = $course->reviews;
        $canReview = auth()->user()->can('create', [Review::class, $course]);
        $hasReviewed = auth()->user()->isStudent()
            && $course->reviews->contains('user_id', auth()->id());

        return view('courses.show', compact('course', 'enrollment', 'reviews', 'canReview', 'hasReviewed'));
    }
}
