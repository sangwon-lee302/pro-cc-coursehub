<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class SubmissionPolicy
{
    public function submit(User $user, Quiz $quiz, Course $course): bool
    {
        if (Gate::forUser($user)->denies('view', $course)) {
            return false;
        }

        if (! $user->isStudent() || ! $quiz->belongsToCourse($course) || ! $user->isEnrolledIn($course)) {
            return false;
        }

        return ! $quiz->passedBy($user);
    }
}
