<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApprovalAndAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'            => 'Admin Test',
            'email'           => 'admin_test@test.com',
            'password'        => 'SecurePass12',
            'role'            => 'admin',
            'approval_status' => 'approved',
            'is_active'       => true,
        ]);
    }

    /**
     * Test 1: User register menghasilkan approval_status = pending dan is_active = false
     */
    public function test_register_creates_pending_user_and_does_not_login(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Sopian Baru',
            'email'                 => 'sopian@test.com',
            'password'              => 'SecurePass12',
            'password_confirmation' => 'SecurePass12',
            'phone'                 => '08123456789',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('user.approval_status', 'pending');
        $response->assertJsonPath('user.is_active', false);
        $this->assertGuest(); // Memastikan user tidak langsung login

        $user = User::where('email', 'sopian@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('pending', $user->approval_status);
        $this->assertFalse($user->is_active);
    }

    /**
     * Test 2: Login dengan user yang masih pending ditolak
     */
    public function test_pending_user_cannot_login(): void
    {
        User::create([
            'name'            => 'Pending User',
            'email'           => 'pending@test.com',
            'password'        => 'SecurePass12',
            'role'            => 'user',
            'approval_status' => 'pending',
            'is_active'       => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'pending@test.com',
            'password' => 'SecurePass12',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test 3: Admin dapat menyetujui (ACC) akun pending
     */
    public function test_admin_can_approve_pending_user(): void
    {
        $pendingUser = User::create([
            'name'            => 'Menunggu Acc',
            'email'           => 'menunggu@test.com',
            'password'        => 'SecurePass12',
            'role'            => 'user',
            'approval_status' => 'pending',
            'is_active'       => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/users/{$pendingUser->id}/approve");

        $response->assertStatus(200);
        $response->assertJsonPath('user.approval_status', 'approved');
        $response->assertJsonPath('user.is_active', true);

        $pendingUser->refresh();
        $this->assertEquals('approved', $pendingUser->approval_status);
        $this->assertTrue($pendingUser->is_active);
    }

    /**
     * Test 4: User yang disetujui dapat login
     */
    public function test_approved_user_can_login(): void
    {
        User::create([
            'name'            => 'Approved User',
            'email'           => 'approved@test.com',
            'password'        => 'SecurePass12',
            'role'            => 'user',
            'approval_status' => 'approved',
            'is_active'       => true,
        ]);

        $loginRes = $this->postJson('/api/login', [
            'email'    => 'approved@test.com',
            'password' => 'SecurePass12',
        ]);

        $loginRes->assertStatus(200);
        $loginRes->assertJsonPath('user.email', 'approved@test.com');
    }

    /**
     * Test 5: Admin CRUD - Buat Akun Admin Baru
     */
    public function test_admin_can_create_new_admin_account(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/users', [
                'name'                  => 'Admin Kedua',
                'email'                 => 'admin2@test.com',
                'password'              => 'SecurePass12',
                'password_confirmation' => 'SecurePass12',
                'role'                  => 'admin',
                'phone'                 => '089988776655',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('user.role', 'admin');
        $response->assertJsonPath('user.approval_status', 'approved');
        $response->assertJsonPath('user.is_active', true);

        $this->assertDatabaseHas('users', [
            'email' => 'admin2@test.com',
            'role'  => 'admin',
        ]);
    }

    /**
     * Test 6: Admin CRUD - Update Akun
     */
    public function test_admin_can_update_user_account(): void
    {
        $target = User::create([
            'name'            => 'Target Edit',
            'email'           => 'target@test.com',
            'password'        => 'SecurePass12',
            'role'            => 'user',
            'approval_status' => 'approved',
            'is_active'       => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/users/{$target->id}", [
                'name'       => 'Nama Diperbarui',
                'email'      => 'target@test.com',
                'role'       => 'user',
                'phone'      => '081122334455',
                'is_active'  => true,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id'    => $target->id,
            'name'  => 'Nama Diperbarui',
            'phone' => '081122334455',
        ]);
    }

    /**
     * Test 7: Admin CRUD - Hapus Akun & Tidak dapat hapus diri sendiri
     */
    public function test_admin_cannot_delete_self_but_can_delete_other(): void
    {
        // Coba hapus akun sendiri -> gagal 422
        $selfRes = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/users/{$this->admin->id}");
        $selfRes->assertStatus(422);

        // Buat user dummy lalu hapus -> sukses 200
        $dummy = User::create([
            'name'            => 'Dummy User',
            'email'           => 'dummy@test.com',
            'password'        => 'SecurePass12',
            'role'            => 'user',
            'approval_status' => 'approved',
            'is_active'       => true,
        ]);

        $deleteRes = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/users/{$dummy->id}");
        $deleteRes->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $dummy->id]);
    }
}
