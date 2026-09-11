<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use Exception;

/**
 * 受講登録に関するビジネスロジックを集約するサービスクラス
 *
 * EnrollmentController から切り出して責務を分離した。
 * 登録時のバリデーション・Enrollment 作成・初期進捗レコード生成を担当。
 */
class EnrollmentService
{
    public function enroll(User $user, Course $course): Enrollment
    {
        // 受講済みチェック（重複登録防止）
        if ($user->enrollments()->where('course_id', $course->id)->exists()) {
            throw new Exception('既に受講登録済みです');
        }

        // コース公開チェック
        if ($course->status !== 'published') {
            throw new Exception('このコースは現在受講できません');
        }

        // Enrollment 作成
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // 全公開レッスンの進捗レコードを一括作成
        $lessonIds = $course->chapters()
            ->with(['lessons' => fn ($q) => $q->where('is_published', true)])
            ->get()
            ->flatMap->lessons
            ->pluck('id');

        foreach ($lessonIds as $lessonId) {
            LessonProgress::create([
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
                'status' => 'not_started',
            ]);
        }

        return $enrollment;
    }
}
