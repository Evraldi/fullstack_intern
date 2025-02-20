<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php
Route::get('/password/reset/{token}', function ($token) {
    return response()->json([
        'message' => 'pakai token dibawah untuk test postman kwkwkw',
        'token'   => $token,
    ]);
})->name('password.reset');

