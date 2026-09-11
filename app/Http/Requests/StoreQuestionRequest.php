<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.body' => ['required', 'string'],
            'options.*.is_correct' => ['boolean'],
            'correct_option' => ['required', 'integer'],
        ];
    }
}
