<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyCategory extends Model
{
    protected $fillable = [
        'survey_id',
        'name',
        'description',
        'sort_order',
    ];

    /**
     * Survey pemilik kategori ini
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Pertanyaan-pertanyaan dalam kategori ini
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }
}
