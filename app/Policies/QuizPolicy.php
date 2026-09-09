<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function manage(User $user, Quiz $quiz): bool
    {
        return $user->id === $quiz->lesson->chapter->course->user_id;
    }

    public function view(User $user, Quiz $quiz, Course $course): bool
    {
        return $quiz->belongsToCourse($course);
    }

    public function result(User $user, Quiz $quiz, Course $course): bool
    {
        if ($user->role !== 'student') {
            return false;
        }

        if (! $quiz->belongsToCourse($course)) {
            return false;
        }

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();
    }
}
