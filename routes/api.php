<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'show']);
    Route::post('/logout', [AuthController::class, 'destroy']);

    Route::get('/leads', [LeadController::class, 'index']);
    Route::get('/leads/{lead}', [LeadController::class, 'show']);
});

Route::post('/webhook/leads', [WebhookController::class, 'store'])
    ->middleware('validate.lead.webhook');
    
Route::post('/login', [AuthController::class, 'store']);
