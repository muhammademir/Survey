<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'birth_date',
        'phone',
        'role',
        'approval_status',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Cek apakah user adalah admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah akun user telah disetujui
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Cek apakah akun user masih menunggu persetujuan
     */
    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Cek apakah pendaftaran akun user ditolak
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Survey responses milik user ini
     */
    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Hapus token Sanctum dan session database milik user ini.
     */
    public function revokeAccess(): void
    {
        $this->tokens()->delete();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->id)
                ->delete();
        }
    }

    /**
     * True jika user ini adalah satu-satunya admin yang masih aktif.
     */
    public function isLastActiveAdmin(): bool
    {
        if ($this->role !== 'admin' || ! $this->is_active) {
            return false;
        }

        return static::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->count() <= 1;
    }
}
