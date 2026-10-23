<?php

use App\Http\Middleware\ValidateLeadWebhookSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Refuse to boot on PHP versions affected by the security bug
// fixed in 8.4.27 / 8.5.12. Keep in sync with "php" in composer.json.
$minPatched = match (PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION) {
    '8.4' => 80427,
    '8.5' => 80512,
    // Older branches are unsupported; newer branches are assumed to include the fix.
    default => PHP_VERSION_ID < 80400 ? PHP_INT_MAX : 0,
};

if ($minPatched > PHP_VERSION_ID) {
    throw new RuntimeException(
        'PHP '.PHP_VERSION.' has a known security bug. Upgrade to 8.4.27+ or 8.5.12+.'
    );
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'validate.lead.webhook' => ValidateLeadWebhookSignature::class,
        ]);

        // The Nginx reverse proxy sits in front of PHP-FPM and sets X-Forwarded-*.
        // PHP-FPM is only reachable from that proxy, so trusting every proxy is safe.
        $middleware->trustProxies(at: '*');

        $middleware->preventRequestForgery(except: [
            'webhook/leads',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
