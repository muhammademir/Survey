<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Registrasi user baru (role = user)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        // Akun tidak langsung masuk, menunggu persetujuan (acc) dari admin
        return response()->json([
            'message' => 'Registrasi berhasil! Akun Anda sedang menunggu persetujuan dari administrator.',
            'user'    => new UserResource($user),
        ], 201);
    }

    /**
     * Login (admin & user biasa) — cookie-based session
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login($request->validated());

        Auth::guard('web')->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json([
            'message' => 'Login berhasil',
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * Logout — destroy session
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Get user yang sedang login
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
