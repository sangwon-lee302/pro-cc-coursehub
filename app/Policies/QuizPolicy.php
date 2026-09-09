<?php

namespace App\Policies;

use App\Models\Course;
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
}
