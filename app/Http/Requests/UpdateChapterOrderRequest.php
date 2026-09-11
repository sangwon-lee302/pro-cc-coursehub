<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChapterOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => ['required', 'array'],
            'order.*' => ['integer', Rule::exists('chapters', 'id')->where('course_id', $this->route('course')->id)],
        ];
    }
}
