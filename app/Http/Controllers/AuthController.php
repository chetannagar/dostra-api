<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle the incoming login request.
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $user = \App\Models\User::query()
            ->with('clients')
            ->where('email', $request->input('email'))
            ->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $token = $user->createToken('crm')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Display the authenticated user's information.
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('clients')
        );
    }

    /**
     * Handle the logout request.
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
