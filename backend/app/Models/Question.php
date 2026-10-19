<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'survey_category_id',
        'question_text',
        'sort_order',
    ];

    /**
     * Kategori pemilik pertanyaan ini
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SurveyCategory::class, 'survey_category_id');
    }

    /**
     * Opsi jawaban untuk pertanyaan ini
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderByRaw("FIELD(label, 'A', 'B', 'C', 'D')");
    }
}
