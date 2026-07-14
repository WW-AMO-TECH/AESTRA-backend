<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * ADMIN / SUPER ADMIN LOGIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        // user not found
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // password check
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Incorrect password'
            ], 401);
        }

        // blocked check
        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Account is blocked'
            ], 403);
        }

        // must be admin or super admin
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json([
                'message' => 'You are not allowed to access admin login'
            ], 403);
        }

        // admin approval check
        if ($user->role === 'admin' && $user->status !== 'active') {
            return response()->json([
                'message' => 'Admin account not approved yet'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * ADMIN SIGNUP REQUEST
     */
    public function signupRequest(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'status' => 'pending', // 🔥 important for approval system
            'is_blocked' => false
        ]);

        return response()->json([
            'message' => 'Admin request submitted. Awaiting approval.',
            'user' => $user
        ], 201);
    }

    /**
     * GET CURRENT ADMIN
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}