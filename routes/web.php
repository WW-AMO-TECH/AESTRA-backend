<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\GoogleAuthController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get(
    '/auth/google/{type}',
    [GoogleAuthController::class, 'redirect']
)->where('type', 'user|seller');

Route::get(
    '/auth/google/{type}/callback',
    [GoogleAuthController::class, 'callback']
)->where('type', 'user|seller');
