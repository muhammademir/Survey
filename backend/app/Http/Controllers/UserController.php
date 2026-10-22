<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * [Admin] Daftar semua user & admin beserta filter dan statistik
     */
    public function index(IndexUserRequest $request): JsonResponse
    {
        $query = User::query();
        $validated = $request->validated();

        if (! empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (! empty($validated['approval_status'])) {
            $query->where('approval_status', $validated['approval_status']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Sertakan statistik ringkas untuk kemudahan UI
        $stats = [
            'total'     => User::count(),
            'admins'    => User::where('role', 'admin')->count(),
            'users'     => User::where('role', 'user')->count(),
            'pending'   => User::where('approval_status', 'pending')->count(),
            'active'    => User::where('is_active', true)->count(),
        ];

        $payload = $users->toArray();
        $payload['data'] = UserResource::collection($users->getCollection())->resolve();
        $payload['stats'] = $stats;

        return response()->json($payload);
    }

    /**
     * [Admin] Tambah user baru (khususnya Akun Admin / Pengguna)
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'            => $data['name'],
            'email'           => $data['email'],
            'password'        => $data['password'],
            'role'            => $data['role'],
            'birth_date'      => $data['birth_date'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'is_active'       => $data['is_active'] ?? true,
            'approval_status' => 'approved', // Akun yang dibuat langsung oleh Admin otomatis approved
        ]);

        return response()->json([
            'message' => $user->role === 'admin'
                ? 'Akun administrator baru berhasil dibuat'
                : 'Akun pengguna baru berhasil dibuat',
            'user'    => new UserResource($user),
        ], 201);
    }

    /**
     * [Admin] Lihat detail user
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * [Admin] Perbarui akun admin atau user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        // Mencegah admin mendowngrade akunnya sendiri jika sedang login
        if ($user->id === auth()->id() && isset($data['role']) && $data['role'] !== 'admin') {
            return response()->json([
                'message' => 'Anda tidak dapat mengubah peran akun administrator Anda sendiri.',
            ], 422);
        }

        $willBeAdmin = ($data['role'] ?? $user->role) === 'admin';
        $willBeActive = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : $user->is_active;

        if ($user->isLastActiveAdmin() && (! $willBeAdmin || ! $willBeActive)) {
            return response()->json([
                'message' => 'Tidak dapat menonaktifkan atau mengubah peran satu-satunya administrator yang aktif.',
            ], 422);
        }

        // Siapkan update payload
        $updateData = [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'role'       => $data['role'],
            'birth_date' => $data['birth_date'] ?? null,
            'phone'      => $data['phone'] ?? null,
        ];

        if (isset($data['is_active'])) {
            $updateData['is_active'] = (bool) $data['is_active'];
        }

        if (isset($data['approval_status'])) {
            $updateData['approval_status'] = $data['approval_status'];
        }

        $passwordChanged = ! empty($data['password']);
        if ($passwordChanged) {
            $updateData['password'] = $data['password'];
        }

        $user->update($updateData);

        if ($passwordChanged || (isset($updateData['is_active']) && ! $updateData['is_active'])) {
            $user->revokeAccess();
        }

        return response()->json([
            'message' => 'Data akun berhasil diperbarui',
            'user'    => new UserResource($user->fresh()),
        ]);
    }

    /**
     * [Admin] Hapus akun admin atau user
     */
    public function destroy(User $user): JsonResponse
    {
        // Mencegah menghapus akun sendiri
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.',
            ], 422);
        }

        // Mencegah menghapus admin terakhir
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'message' => 'Tidak dapat menghapus satu-satunya administrator yang ada di sistem.',
            ], 422);
        }

        $userName = $user->name;
        $user->revokeAccess();
        $user->delete();

        return response()->json([
            'message' => "Akun {$userName} berhasil dihapus.",
        ]);
    }

    /**
     * [Admin] Setujui (ACC) akun pengguna yang mendaftar
     */
    public function approve(User $user): JsonResponse
    {
        $user->approval_status = 'approved';
        $user->is_active = true;
        $user->save();

        return response()->json([
            'message' => "Akun {$user->name} berhasil disetujui (ACC). Pengguna sekarang dapat masuk ke sistem.",
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * [Admin] Tolak akun pengguna yang mendaftar
     */
    public function reject(User $user): JsonResponse
    {
        $user->approval_status = 'rejected';
        $user->is_active = false;
        $user->save();
        $user->revokeAccess();

        return response()->json([
            'message' => "Pendaftaran akun {$user->name} telah ditolak.",
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * [Admin] Toggle status aktif user (enable/disable)
     */
    public function toggleStatus(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Tidak dapat mengubah status akun sendiri.',
            ], 422);
        }

        if ($user->is_active && $user->isLastActiveAdmin()) {
            return response()->json([
                'message' => 'Tidak dapat menonaktifkan satu-satunya administrator yang aktif.',
            ], 422);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        if (! $user->is_active) {
            $user->revokeAccess();
        }

        return response()->json([
            'message' => $user->is_active
                ? 'User berhasil diaktifkan'
                : 'User berhasil dinonaktifkan',
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * [User Biasa] Update profil sendiri
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        // Defense-in-depth: hanya izinkan field yang benar-benar boleh diubah user
        $user->update($request->safe()->only(['name', 'email', 'birth_date', 'phone']));

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user'    => new UserResource($user->fresh()),
        ]);
    }
}
