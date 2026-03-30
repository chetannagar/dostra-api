<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Lead::query()->with('client')->latest();

        if (! $user->isAdmin()) {
            $clientIds = $user->clients()->pluck('clients.id');

            $query->whereIn('client_id', $clientIds);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        return response()->json(
            $lead->load('client')
        );
    }
}
