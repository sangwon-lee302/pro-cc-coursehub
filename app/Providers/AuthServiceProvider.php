<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    // TODO: Course/Chapter/Lesson/Quiz はモデル名 + "Policy" という命名規則に沿っており、
    // Laravel の Policy 自動解決（app/Policies 配下を規約でマッピング）で本来は明示登録不要。
    // EnrollmentPolicy/SubmissionPolicy もここに載せず自動解決に任せているのと扱いが揃っておらず、
    // 一貫性のためこの配列自体を削除できる可能性がある（将来のリファクタリング候補）。
    protected $policies = [
        \App\Models\Course::class => \App\Policies\CoursePolicy::class,
        \App\Models\Chapter::class => \App\Policies\ChapterPolicy::class,
        \App\Models\Lesson::class => \App\Policies\LessonPolicy::class,
        \App\Models\Quiz::class => \App\Policies\QuizPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
