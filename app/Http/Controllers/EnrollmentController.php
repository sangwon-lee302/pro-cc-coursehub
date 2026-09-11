<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\EnrollmentService;
use Exception;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService
    ) {}

    public function store(Course $course)
    {
        try {
            $this->enrollmentService->enroll(auth()->user(), $course);
        } catch (Exception $e) {
            return redirect()->route('courses.show', $course)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('courses.show', $course)
            ->with('success', 'コースに登録しました。');
    }
}
