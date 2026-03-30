<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadWebhookRequest;
use App\Http\Resources\LeadResource;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function store(StoreLeadWebhookRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->attributes->get('webhookClient');

        /** @var string $eventId */
        $eventId = $request->attributes->get('webhookEventId');

        $existingLead = Lead::query()
            ->where('webhook_event_id', $eventId)
            ->first();

        if ($existingLead instanceof Lead) {
            return response()->json([
                'message' => 'Webhook event already processed.',
                'data' => new LeadResource($existingLead->load('client')),
            ]);
        }

        $lead = Lead::query()->create([
            'client_id' => $client->id,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'message' => $request->input('message'),
            'source' => 'wpforms-zapier',
            'webhook_event_id' => $eventId,
            'raw_payload' => $request->json()->all(),
        ]);

        return response()->json([
            'message' => 'Lead received successfully',
            'data' => new LeadResource($lead->load('client')),
        ], 201);
    }
}
