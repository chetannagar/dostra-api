<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexLeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function index(IndexLeadRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Lead::query()
            ->with('client')
            ->latest();

        if (! $user->isAdmin()) {
            $query->whereIn(
                'client_id',
                $user->clients()->pluck('clients.id')
            );
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        if ($request->filled('email')) {
            $query->where('email', $request->string('email')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($leadQuery) use ($search): void {
                $leadQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        return LeadResource::collection(
            $query->paginate($request->integer('per_page', 15))
                ->withQueryString()
        );
    }

    public function show(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        return response()->json(
            new LeadResource($lead->load('client'))
        );
    }
}
