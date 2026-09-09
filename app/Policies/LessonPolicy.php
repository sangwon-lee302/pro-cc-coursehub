<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Enrollment;
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

    public function complete(User $user, Lesson $lesson, Course $course): bool
    {
        if ($user->role !== 'student') {
            return false;
        }

        if (! $lesson->belongsToCourse($course)) {
            return false;
        }

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();
    }
}
