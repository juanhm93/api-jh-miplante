<?php

use App\Http\Controllers\Api\CreditoController;
use App\Http\Controllers\Api\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [LoginController::class, 'store'])
    ->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/creditos/resumen', [CreditoController::class, 'resumen'])
        ->name('creditos.resumen');
});
