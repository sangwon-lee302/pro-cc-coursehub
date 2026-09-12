<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Course::class);
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string'],
            'difficulty' => ['required', 'in:beginner,intermediate,advanced'],
            'status' => ['required', 'in:draft,published'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            // カンマ区切りで新規タグを指定可能
            'new_tags' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.unique' => '同じタイトルのコースが既に存在します。',
            'category_id.required' => 'カテゴリーを選択してください。',
            'category_id.exists' => '選択されたカテゴリーが存在しません。',
            'description.required' => '説明文は必須です。',
            'difficulty.required' => '難易度を選択してください。',
            'difficulty.in' => '難易度の指定が不正です。',
            'status.required' => '公開ステータスを選択してください。',
            'status.in' => '公開ステータスの指定が不正です。',
            'image.image' => '画像形式のファイルを指定してください。',
            'image.mimes' => '画像はjpeg, png, jpg, gif形式で指定してください。',
            'image.max' => '画像サイズは2MB以内にしてください。',
            'tags.*.exists' => '選択されたタグが存在しません。',
        ];
    }
}
