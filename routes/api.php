<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PharmacistController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\RatingController;

Route::post('/register/pharmacist', [PharmacistController::class, 'registerPharmacist']);
Route::post('/register/pharmacy', [PharmacistController::class, 'registerPharmacy']);
Route::post('/login', [PharmacistController::class, 'login']);
Route::post('/admin/pharmacy/{id}/approve', [PharmacistController::class, 'approvePharmacy']);
Route::post('/admin/pharmacy/{id}/rejectPharmacy', [PharmacistController::class, 'rejectPharmacy']);
Route::post('/logout', [PharmacistController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sale/create', [SaleController::class, 'createSale']);
    Route::get('/sale/daily', [SaleController::class, 'getDailySales']);
    Route::get('/medicines', [MedicineController::class, 'getMedicines']);
    Route::get('/medicines/search', [MedicineController::class, 'searchMedicine']);
    Route::post('/medicines/add', [MedicineController::class, 'addMedicine']);
    Route::post('/medicines/edit/{id}', [MedicineController::class, 'editMedicine']);
    Route::get('/medicines/expiring', [MedicineController::class, 'getExpiringMedicines']);
    Route::get('/medicines/low-stock', [MedicineController::class, 'getLowStockMedicines']);
    Route::post('/orders/{id}/receive', [OrderController::class, 'receiveOrder']);
    Route::get('/orders', [OrderController::class, 'getOrders']);
    Route::get('/orders', [OrderController::class, 'createOrder']);
    Route::get('/medicines/search', [MedicineController::class, 'search']);
    Route::get('/Profile', [PharmacistController::class, 'getProfile']);


    Route::post('/rating', [RatingController::class, 'submitRating']);
    Route::post('/logout', [PharmacistController::class, 'logout']);
    Route::delete('/delete-account', [PharmacistController::class, 'deleteAccount']);
});
