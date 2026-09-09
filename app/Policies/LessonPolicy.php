<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function manage(User $user, Lesson $lesson, Chapter $chapter, Course $course): bool
    {
        return $lesson->belongsToChapter($chapter)
            && $chapter->belongsToCourse($course)
            && $user->id === $course->user_id;
    }

    public function view(User $user, Lesson $lesson, Course $course): bool
    {
        return $lesson->belongsToCourse($course);
    }
}
