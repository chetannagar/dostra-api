<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle the incoming login request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $user = User::query()
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
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('clients')
        );
    }

    /**
     * Handle the logout request.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
