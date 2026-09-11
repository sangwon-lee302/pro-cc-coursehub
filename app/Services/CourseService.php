<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use App\Support\SlugGenerator;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * コース作成に関するビジネスロジックを集約するサービスクラス
 *
 * CoachCourseController から切り出して責務を分離した。
 * スラッグ生成・画像保存・タグ同期（既存+新規）・初期チャプター作成を担当。
 */
class CourseService
{
    public function create(User $coach, array $data, ?UploadedFile $image): Course
    {
        $course = Course::create([
            'user_id' => $coach->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'slug' => $this->generateUniqueSlug($data['title']),
            'description' => $data['description'],
            'difficulty' => $data['difficulty'],
            'image_path' => $image ? $this->storeImage($image) : null,
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        $this->syncTags($course, $data['tags'] ?? [], $data['new_tags'] ?? null);
        $this->createInitialChapter($course);

        return $course;
    }

    private function generateUniqueSlug(string $title): string
    {
        return SlugGenerator::unique('courses', $title, 'course');
    }

    private function storeImage(UploadedFile $image): string
    {
        $fileName = time().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
        $path = $image->storeAs('courses', $fileName, 'public');

        if (! $path) {
            throw new Exception('画像のアップロードに失敗しました。');
        }

        return $path;
    }

    private function syncTags(Course $course, array $tagIds, ?string $newTagsCsv): void
    {
        if (! empty($newTagsCsv)) {
            foreach (array_map('trim', explode(',', $newTagsCsv)) as $tagName) {
                if ($tagName === '') {
                    continue;
                }

                $slug = Str::slug($tagName);

                // 非ASCII文字のみのタグ名は slug が空文字列になり区別できないため、
                // その場合は name の完全一致でタグを探す
                $tag = $slug !== ''
                    ? Tag::firstOrCreate(['slug' => $slug], ['name' => $tagName])
                    : Tag::firstOrCreate(['name' => $tagName], ['slug' => SlugGenerator::unique('tags', $tagName, 'tag')]);

                if (! in_array($tag->id, $tagIds)) {
                    $tagIds[] = $tag->id;
                }
            }
        }

        if (! empty($tagIds)) {
            $course->tags()->sync($tagIds);
        }
    }

    private function createInitialChapter(Course $course): void
    {
        // コース作成時に最初のチャプターを自動生成
        // これにより、コーチがすぐにレッスンを追加できる
        Chapter::create([
            'course_id' => $course->id,
            'title' => 'はじめに',
            'order' => 1,
        ]);
    }
}
