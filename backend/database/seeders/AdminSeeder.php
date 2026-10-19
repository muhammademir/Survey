<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * Buat akun admin dari environment — tidak ada password default di kode.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD belum di-set. Seeder admin dilewati.');

            return;
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('ADMIN_PASSWORD harus minimal 12 karakter.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'            => 'Administrator',
                'password'        => $password,
                'role'            => 'admin',
                'approval_status' => 'approved',
                'is_active'       => true,
            ]
        );
    }
}
