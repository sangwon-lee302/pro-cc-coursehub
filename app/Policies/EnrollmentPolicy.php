<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function enroll(User $user, Course $course): bool
    {
        if (! $user->isStudent()) {
            return false;
        }

        if ($course->status !== 'published') {
            return false;
        }

        $alreadyEnrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        return !$alreadyEnrolled;
    }
}
