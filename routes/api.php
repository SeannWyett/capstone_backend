<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\ApiLoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaperController;
use App\Http\Controllers\LocationController;

Route::post('/login', [ApiLoginController::class, 'login']);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
    });
    
    Route::get('/papers', [PaperController::class, 'index']);
    Route::get('/papers/{id}', [PaperController::class, 'show']);
    Route::post('/papers/{id}/view', [PaperController::class, 'recordView']);
    Route::get('/papers/{paperUpload}/file', [PaperController::class, 'viewFile']);
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/papers/{paperUpload}/file', [PaperController::class, 'viewFile']);

    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/papers', [PaperController::class, 'store']);
        Route::put('/papers/{id}', [PaperController::class, 'update']);
        Route::delete('/papers/{id}', [PaperController::class, 'destroy']);
        Route::post('/logout', [ApiLoginController::class, 'logout']);
        Route::post('/campus', [LocationController::class, 'addCampus']);
        Route::post('/department', [LocationController::class, 'addDepartment']);
        Route::post('/program', [LocationController::class, 'addProgram']);
});
