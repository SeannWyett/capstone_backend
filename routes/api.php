<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\ApiLoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaperController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\CategoryController;

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
    Route::get('/analytics', [PaperController::class, 'analytics']);
    Route::get('/category', [CategoryController::class, 'index']);

    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/papers', [PaperController::class, 'store']);
        Route::put('/papers/{id}', [PaperController::class, 'update']);
        Route::delete('/papers/{id}', [PaperController::class, 'destroy']);
        Route::post('/logout', [ApiLoginController::class, 'logout']);

        //location routes
        Route::post('/campus', [LocationController::class, 'addCampus']);
        Route::put('/campus/{id}', [LocationController::class, 'updateCampus']);
        Route::delete('/campus/{id}', [LocationController::class, 'destroyCampus']);
        Route::post('/college', [LocationController::class, 'addCollege']);
        Route::put('/college/{id}', [LocationController::class, 'updateCollege']);
        Route::delete('/college/{id}', [LocationController::class, 'destroyCollege']);
        Route::post('/program', [LocationController::class, 'addProgram']);
        Route::put('/program/{id}', [LocationController::class, 'updateProgram']);
        Route::delete('/program/{id}', [LocationController::class, 'destroyProgram']);

        //category routes
        Route::post('/category', [CategoryController::class, 'store']);
        Route::put('/category/{id}', [CategoryController::class, 'update']);
        Route::delete('/category/{id}', [CategoryController::class, 'destroy']);
});
