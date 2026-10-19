<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveUserMiddleware
{
    /**
     * Handle an incoming request - pastikan user aktif
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->approval_status === 'pending') {
                return response()->json([
                    'message' => 'Akun Anda sedang menunggu persetujuan dari administrator.',
                ], 403);
            }

            if ($user->approval_status === 'rejected') {
                return response()->json([
                    'message' => 'Pendaftaran akun Anda ditolak oleh administrator.',
                ], 403);
            }

            if (! $user->is_active) {
                return response()->json([
                    'message' => 'Akun Anda telah dinonaktifkan.',
                ], 403);
            }
        }

        return $next($request);
    }
}
