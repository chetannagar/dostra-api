<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});
Route::get('/leads', [LeadController::class, 'index']);
Route::get('/leads/{lead}', [LeadController::class, 'show']);
Route::post('/webhook/leads', [WebhookController::class, 'store']);
