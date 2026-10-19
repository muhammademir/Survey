<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    protected $table = 'questions_options';

    protected $fillable = [
        'question_id',
        'label',
        'option_text',
    ];

    /**
     * Pertanyaan pemilik opsi ini
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
