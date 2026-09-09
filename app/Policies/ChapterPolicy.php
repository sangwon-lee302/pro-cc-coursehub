<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\User;

class ChapterPolicy
{
    public function manage(User $user, Chapter $chapter, Course $course): bool
    {
        return $chapter->belongsToCourse($course) && $user->id === $course->user_id;
    }
}
