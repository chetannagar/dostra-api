<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();

        $lead = Lead::query()->create([
            'client_id' => $request->string('client_id')->toString(),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'message' => $request->input('message'),
            'source' => 'wpforms-zapier',
            'raw_payload' => $payload,
        ]);

        return response()->json([
            'message' => 'Lead received successfully',
            'data' => $lead,
        ], 201);
    }
}
