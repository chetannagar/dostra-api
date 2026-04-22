<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query()->latest();

        if (! request()->user()->isAdmin()) {
            $query->whereIn('id', request()->user()->clients()->pluck('clients.id'));
        }

        return ClientResource::collection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request): ClientResource
    {
        $this->authorize('create', Client::class);

        $validated = $request->validated();

        $client = Client::query()->create([
            'name' => $validated['name'],
            'client_key' => $validated['client_key'] ?? Client::generateClientKey($validated['name']),
            'webhook_secret' => $validated['webhook_secret'] ?? Client::generateWebhookSecret(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return new ClientResource($client);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client): ClientResource
    {
        $this->authorize('view', $client);

        return new ClientResource($client);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        $this->authorize('update', $client);

        $validated = $request->validated();

        $client->fill([
            'name' => $validated['name'] ?? $client->name,
            'client_key' => $validated['client_key'] ?? $client->client_key,
            'is_active' => $validated['is_active'] ?? $client->is_active,
        ]);

        if (array_key_exists('webhook_secret', $validated)) {
            $client->webhook_secret = $validated['webhook_secret'];
        }

        $client->save();

        return new ClientResource($client);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
