<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/leads', [WebhookController::class, 'store'])
    ->middleware(['validate.lead.webhook', 'throttle:webhook']);

Route::post('/login', [AuthController::class, 'store'])
    ->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:api.crm'])->group(function (): void {
    Route::get('/me', [AuthController::class, 'show']);
    Route::post('/logout', [AuthController::class, 'destroy']);

    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    Route::put('/clients/{client}', [ClientController::class, 'update']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);

    Route::get('/leads', [LeadController::class, 'index']);
    Route::get('/leads/{lead}', [LeadController::class, 'show']);
});
