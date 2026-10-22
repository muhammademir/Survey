<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexSurveyRequest extends FormRequest
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
            'status' => 'nullable|in:draft,published',
            'search' => 'nullable|string|max:100',
            'page'   => 'nullable|integer|min:1',
        ];
    }
}
