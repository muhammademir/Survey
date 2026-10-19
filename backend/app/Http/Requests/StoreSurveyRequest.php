<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyRequest extends FormRequest
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
            'title'                                   => 'required|string|max:255',
            'description'                             => 'nullable|string',
            'publish_start_at'                        => 'nullable|date',
            'publish_end_at'                          => 'nullable|date|after_or_equal:publish_start_at',
            'categories'                              => 'required|array|min:1',
            'categories.*.name'                       => 'required|string|max:255',
            'categories.*.description'                => 'nullable|string',
            'categories.*.questions'                  => 'required|array|min:1',
            'categories.*.questions.*.question_text'  => 'required|string',
            'categories.*.questions.*.options'        => 'required|array|size:4',
            'categories.*.questions.*.options.*.label'       => 'required|string|in:A,B,C,D',
            'categories.*.questions.*.options.*.option_text' => 'required|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required'                              => 'Judul survey wajib diisi.',
            'categories.required'                         => 'Minimal 1 kategori harus ditambahkan.',
            'categories.*.name.required'                  => 'Nama kategori wajib diisi.',
            'categories.*.questions.required'             => 'Setiap kategori harus memiliki minimal 1 pertanyaan.',
            'categories.*.questions.*.question_text.required' => 'Teks pertanyaan wajib diisi.',
            'categories.*.questions.*.options.required'   => 'Setiap pertanyaan harus memiliki opsi jawaban.',
            'categories.*.questions.*.options.size'       => 'Setiap pertanyaan harus memiliki tepat 4 opsi (A, B, C, D).',
        ];
    }
}
