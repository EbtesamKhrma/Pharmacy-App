<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PharmacistController;

Route::post('/register/pharmacist', [PharmacistController::class, 'registerPharmacist']);
Route::post('/register/pharmacy', [PharmacistController::class, 'registerPharmacy']);
Route::post('/login', [PharmacistController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {

});
