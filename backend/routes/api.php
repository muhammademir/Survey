<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyResponseController;
use App\Http\Controllers\SurveyResultController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ===== AUTH (public) =====
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// ===== AUTHENTICATED =====
Route::middleware(['auth:sanctum', 'active_user'])->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // User - update profil sendiri
    Route::put('/profile', [UserController::class, 'updateProfile']);

    // ===== USER BIASA: Survey =====
    Route::prefix('surveys')->group(function () {
        Route::get('/available', [SurveyResponseController::class, 'availableSurveys']);
        Route::get('/{survey}/fill', [SurveyResponseController::class, 'showSurveyForFill']);
        Route::post('/{survey}/submit', [SurveyResponseController::class, 'submitResponse']);
        Route::get('/my-history', [SurveyResponseController::class, 'myHistory']);
    });

    // ===== ADMIN =====
    Route::middleware('admin')->prefix('admin')->group(function () {

        // Kelola User & Admin (CRUD + Persetujuan Akun)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch('/users/{user}/approve', [UserController::class, 'approve']);
        Route::patch('/users/{user}/reject', [UserController::class, 'reject']);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

        // Kelola Survey
        Route::get('/surveys', [SurveyController::class, 'index']);
        Route::post('/surveys', [SurveyController::class, 'store']);
        Route::get('/surveys/{survey}', [SurveyController::class, 'show']);
        Route::put('/surveys/{survey}', [SurveyController::class, 'update']);
        Route::patch('/surveys/{survey}/publish', [SurveyController::class, 'publish']);
        Route::delete('/surveys/{survey}', [SurveyController::class, 'destroy']);

        // Hasil Survey
        Route::get('/surveys/{survey}/results', [SurveyResultController::class, 'summary']);
        Route::get('/surveys/{survey}/respondents', [SurveyResultController::class, 'respondents']);
    });
});
