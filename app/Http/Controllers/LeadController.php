<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Lead::query()->latest()->get()
        );
    }

    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead);
    }
}
