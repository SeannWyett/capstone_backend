<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaperController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Route::get('/papers', [PaperController::class, 'index']);
// Route::get('/papers/{id}', [PaperController::class, 'show']);

// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/papers', [PaperController::class, 'store']);
//     Route::put('/papers/{id}', [PaperController::class, 'update']);
//     Route::delete('/papers/{id}', [PaperController::class, 'destroy']);
// });


require __DIR__.'/auth.php';
