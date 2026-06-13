<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PharmacistController;

Route::post('/register', [PharmacistController::class, 'register']);
Route::post('/register2', [PharmacistController::class, 'register2']);
Route::post('/login', [PharmacistController::class, 'login']);

