<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookController;
use App\Http\Controllers\API\UserController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    // Email verification (harus di luar auth)
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');

    // Authenticated routes
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        // Auth related
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);
        
        // Book resources
        Route::apiResource('books', BookController::class);
        
        // Token management restricted to admin only
        Route::get('/tokens', function (Request $request) {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
            }
            return $request->user()->tokens;
        });

        Route::post('/tokens', function (Request $request) {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
            }
            $request->validate(['name' => 'required|string']);
            return $request->user()->createToken($request->name);
        });

        Route::delete('/tokens/{id}', function (Request $request, $id) {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
            }
            $request->user()->tokens()->where('id', $id)->delete();
            return response()->noContent();
        });

        Route::delete('/tokens', function (Request $request) {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
            }
            $request->user()->tokens()->delete();
            return response()->noContent();
        });

        Route::middleware(['admin'])->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::put('/users/{id}/role', [UserController::class, 'updateRole']);
        });
    });
});