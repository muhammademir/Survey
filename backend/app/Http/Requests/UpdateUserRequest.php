<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name'            => 'required|string|max:255',
            'email'           => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password'        => ['nullable', 'confirmed', Password::defaults()],
            'role'            => 'required|in:admin,user',
            'birth_date'      => 'nullable|date',
            'phone'           => 'nullable|string|max:20',
            'is_active'       => 'nullable|boolean',
            'approval_status' => 'nullable|in:pending,approved,rejected',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'Nama wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.unique'      => 'Email sudah digunakan oleh akun lain.',
            'password.min'      => 'Password minimal 12 karakter.',
            'role.required'     => 'Role wajib dipilih.',
            'role.in'           => 'Role harus berupa admin atau user.',
        ];
    }
}
