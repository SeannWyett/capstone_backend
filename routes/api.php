<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\ApiLoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\StudentRegisterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaperController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\CategoryController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\UserController;

Route::post('/login', [ApiLoginController::class, 'login']);
Route::post('/register', [StudentRegisterController::class, 'register']);
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json(['message' => 'Email verified successfully. You can now log in.'], 200);
})->middleware(['signed'])->name('verification.verify');
Route::get('email/resend', [StudentRegisterController::class, 'resend']);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
    });
    
    Route::get('/papers', [PaperController::class, 'index']);
    Route::get('/papers/{id}', [PaperController::class, 'show']);
    Route::post('/papers/{id}/view', [PaperController::class, 'incrementViews']);
    Route::get('/papers/{paperUpload}/file', [PaperController::class, 'viewFile']);
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/analytics', [PaperController::class, 'analytics']);
    Route::get('/category', [CategoryController::class, 'index']);
        
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/papers', [PaperController::class, 'store']);
        Route::put('/papers/{id}', [PaperController::class, 'update']);
        Route::delete('/papers/{id}', [PaperController::class, 'destroy']);
        Route::post('/logout', [ApiLoginController::class, 'logout']);

        //location routes
        Route::post('/locations', [LocationController::class, 'addLocation']);
        Route::put('/locations/{id}', [LocationController::class, 'updateLocation']);
        Route::delete('/campus/{id}', [LocationController::class, 'destroyCampus']);

        //superadmin routes
        Route::post('/admin/campus-admins', [UserController::class, 'createCampusAdmin']);
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);

        // Route::post('/campus', [LocationController::class, 'addCampus']);
        // Route::put('/campus/{id}', [LocationController::class, 'updateCampus']);
        // Route::delete('/campus/{id}', [LocationController::class, 'destroyCampus']);
        // Route::post('/college', [LocationController::class, 'addCollege']);
        // Route::put('/college/{id}', [LocationController::class, 'updateCollege']);
        // Route::delete('/college/{id}', [LocationController::class, 'destroyCollege']);
        // Route::post('/program', [LocationController::class, 'addProgram']);
        // Route::put('/program/{id}', [LocationController::class, 'updateProgram']);
        // Route::delete('/program/{id}', [LocationController::class, 'destroyProgram']);

        //category routes
        // Route::post('/category', [CategoryController::class, 'store']);
        // Route::put('/category/{id}', [CategoryController::class, 'update']);
        // Route::delete('/category/{id}', [CategoryController::class, 'destroy']);
});
