<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/leads', [LeadController::class, 'index']);
Route::get('/leads/{lead}', [LeadController::class, 'show']);

Route::post('/webhook/leads', [WebhookController::class, 'store'])
    ->middleware('validate.lead.webhook');
