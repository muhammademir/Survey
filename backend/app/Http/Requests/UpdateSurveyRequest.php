<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title'                                   => 'sometimes|required|string|max:255',
            'description'                             => 'nullable|string',
            'publish_start_at'                        => 'nullable|date',
            'publish_end_at'                          => 'nullable|date|after_or_equal:publish_start_at',
            'categories'                              => 'sometimes|required|array|min:1',
            'categories.*.name'                       => 'required|string|max:255',
            'categories.*.description'                => 'nullable|string',
            'categories.*.questions'                  => 'required|array|min:1',
            'categories.*.questions.*.question_text'  => 'required|string',
            'categories.*.questions.*.options'        => 'required|array|size:4',
            'categories.*.questions.*.options.*.label'       => 'required|string|in:A,B,C,D',
            'categories.*.questions.*.options.*.option_text' => 'required|string|max:500',
        ];
    }
}
