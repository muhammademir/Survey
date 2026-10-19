<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Survey extends Model
{
    protected $table = 'survey';

    protected $fillable = [
        'title',
        'description',
        'status',
        'publish_start_at',
        'publish_end_at',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'publish_start_at' => 'datetime',
            'publish_end_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Admin yang membuat survey
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Kategori-kategori dalam survey ini
     */
    public function categories(): HasMany
    {
        return $this->hasMany(SurveyCategory::class)->orderBy('sort_order');
    }

    /**
     * Semua respons untuk survey ini
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Cek apakah survey masih dalam masa tayang
     */
    public function isActive(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        $now = now();
        return $this->publish_start_at <= $now && $this->publish_end_at >= $now;
    }

    /**
     * Cek apakah survey sudah kadaluarsa
     */
    public function isExpired(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        return $this->publish_end_at < now();
    }

    /**
     * Scope untuk survey yang published dan aktif (masih dalam masa tayang)
     */
    public function scopePublishedActive($query)
    {
        return $query->where('status', 'published')
            ->where('publish_start_at', '<=', now())
            ->where('publish_end_at', '>=', now());
    }

    /**
     * Scope untuk semua survey yang sudah published
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
