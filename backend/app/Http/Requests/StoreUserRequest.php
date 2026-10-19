<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => ['required', 'confirmed', Password::defaults()],
            'password_confirmation' => 'required|string',
            'role'                  => 'required|in:admin,user',
            'birth_date'            => 'nullable|date',
            'phone'                 => 'nullable|string|max:20',
            'is_active'             => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'                  => 'Nama wajib diisi.',
            'email.required'                 => 'Email wajib diisi.',
            'email.unique'                   => 'Email sudah terdaftar.',
            'password.required'              => 'Password wajib diisi.',
            'password.min'                   => 'Password minimal 12 karakter.',
            'password.confirmed'             => 'Konfirmasi password tidak cocok.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'role.required'                  => 'Role wajib dipilih.',
            'role.in'                        => 'Role harus berupa admin atau user.',
        ];
    }
}
