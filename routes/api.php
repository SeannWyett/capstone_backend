<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\ApiLoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaperController;

Route::post('/login', [ApiLoginController::class, 'login']);
Route::post('/logout', [ApiLoginController::class, 'logout']);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
        return $request->user();
    });

Route::get('/papers', [PaperController::class, 'index']);
Route::get('/papers/capstone', [PaperController::class, 'capstone']);
Route::get('/papers/thesis', [PaperController::class, 'thesis']);
Route::get('/papers/analytics', [PaperController::class, 'analytics']);
Route::get('/papers/thesis/{id}', [PaperController::class, 'show']);
Route::get('/papers/capstone/{id}', [PaperController::class, 'show']);
Route::get('/papers/{id}', [PaperController::class, 'show']);


// Route::middleware('auth:sanctum')->group(function () {
    Route::post('/papers', [PaperController::class, 'store']);
    Route::put('/papers/{id}', [PaperController::class, 'update']);
    Route::delete('/papers/{id}', [PaperController::class, 'destroy']);
// });
