<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ClientWebhookSecretController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Client $client): JsonResponse
    {
        $this->authorize('rotateWebhookSecret', $client);

        $client->forceFill([
            'webhook_secret' => Str::random(64),
        ])->save();

        return response()->json([
            'message' => 'Webhook secret rotated successfully.',
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'client_key' => $client->client_key,
                'webhook_secret' => $client->webhook_secret,
            ],
        ]);
    }
}
