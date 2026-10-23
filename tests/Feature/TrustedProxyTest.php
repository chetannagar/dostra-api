<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('forwarded headers from the reverse proxy are trusted', function () {
    Route::get('/_proxy-probe', fn (Request $request) => [
        'secure' => $request->isSecure(),
        'ip' => $request->ip(),
    ]);

    $this->getJson('/_proxy-probe', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-For' => '203.0.113.7',
    ])->assertOk()->assertExactJson([
        'secure' => true,
        'ip' => '203.0.113.7',
    ]);
});
