<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateLeadWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientKey = $request->header('X-Client-Key');
        $signature = $request->header('X-Webhook-Signature');
        $timestamp = $request->header('X-Webhook-Timestamp');

        if (! is_string($clientKey) || ! is_string($signature) || ! is_string($timestamp)) {
            abort(401, 'Missing webhook authentication headers.');
        }

        $client = Client::query()
            ->where('client_key', $clientKey)
            ->where('is_active', true)
            ->first();

        if (! $client instanceof Client) {
            abort(401, 'Invalid webhook client.');
        }

        if (! ctype_digit($timestamp)) {
            abort(401, 'Invalid webhook timestamp.');
        }

        if (abs(now()->timestamp - (int) $timestamp) > 300) {
            abort(401, 'Webhook timestamp is too old.');
        }

        $payload = $request->getContent();
        $signedPayload = $timestamp.'.'.$payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $client->webhook_secret);

        if (! hash_equals($expectedSignature, $signature)) {
            abort(401, 'Invalid webhook signature.');
        }

        $request->attributes->set('webhookClient', $client);

        return $next($request);
    }
}
