<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadWebhookRequest;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function store(StoreLeadWebhookRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->attributes->get('webhookClient');

        $lead = Lead::query()->create([
            'client_id' => $client->id,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'message' => $request->input('message'),
            'source' => 'wpforms-zapier',
            'raw_payload' => $request->json()->all(),
        ]);

        return response()->json([
            'message' => 'Lead received successfully',
            'data' => $lead,
        ], 201);
    }
}
