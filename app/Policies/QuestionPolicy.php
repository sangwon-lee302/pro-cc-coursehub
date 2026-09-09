<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function manage(User $user, Question $question, Lesson $lesson, Course $course): bool
    {
        return $lesson->quiz
            && $question->belongsToQuiz($lesson->quiz)
            && $lesson->belongsToCourse($course)
            && $user->id === $course->user_id;
    }
}
