<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'description',
        'difficulty',
        'image_path',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'course_tag');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // TODO: hasManyThrough(Lesson::class, Chapter::class) の lessons() リレーションを追加し、
    // $this->lessons()->where('is_published', true)->pluck('id')->toArray() に置き換えられる。
    // 現状は chapters() のクエリ結果を whereIn に渡す2クエリ構成だが、hasManyThrough なら JOIN で1クエリになる。
    public function getAllLessonIds(): array
    {
        return Lesson::whereIn('chapter_id', $this->chapters()->pluck('id'))
            ->where('is_published', true)
            ->pluck('id')
            ->toArray();
    }

    public function getProgressRate($userId): int
    {
        // TODO: 下記の分母計算は is_published を考慮しておらず、getAllLessonIds()（公開済みのみ）と
        // 条件が食い違っている潜在バグ。未公開レッスンがあるコースでは進捗率が実際より低く出る。
        $totalLessons = $this->chapters()->withCount('lessons')->get()
            ->sum('lessons_count');

        $completedLessons = LessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $this->getAllLessonIds())
            ->where('status', 'completed')
            ->count();

        return $totalLessons > 0 ? (int) round($completedLessons / $totalLessons * 100) : 0;
    }
}
