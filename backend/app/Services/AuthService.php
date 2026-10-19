<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register user baru dengan role 'user'.
     */
    public function register(array $data): User
    {
        return User::create([
            'name'            => $data['name'],
            'email'           => $data['email'],
            'password'        => $data['password'],
            'birth_date'      => $data['birth_date'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'role'            => 'user',
            'approval_status' => 'pending',
            'is_active'       => false,
        ]);
    }

    /**
     * Autentikasi user dan buat session.
     *
     * @throws ValidationException
     */
    public function login(array $credentials): User
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if ($user->approval_status === 'pending') {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda sedang menunggu persetujuan dari administrator.'],
            ]);
        }

        if ($user->approval_status === 'rejected') {
            throw ValidationException::withMessages([
                'email' => ['Pendaftaran akun Anda ditolak oleh administrator.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda telah dinonaktifkan. Silakan hubungi administrator.'],
            ]);
        }

        return $user;
    }

    /**
     * Logout user — invalidate session.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }
}
