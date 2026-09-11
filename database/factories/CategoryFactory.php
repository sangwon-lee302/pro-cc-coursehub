<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Web開発', 'モバイル', 'データベース', 'インフラ', 'AI/ML']);
        $slug = Str::slug($name);

        return [
            'name' => $name,
            // 日本語のみの名前は Str::slug() が空文字列を返すため、
            // その場合はランダムな文字列からスラッグを生成して衝突を防ぐ
            'slug' => $slug !== '' ? $slug : Str::slug($name.'-'.Str::random(8)),
        ];
    }
}
