<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * SIGNUP(Register) USER (name, email, phone, password)
     * Role is forced to "user"
     */
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:30',
            'email' => 'required|email|unique:users',
            'phone' => 'required|regex:/^[0-9]+$/|unique:users|min:11|max:14',
            'password' => [
                'required',
                'min:8',
                'regex:/[0-9]/',      // must contain number
                'regex:/[@$!%*#?&]/'  // must contain special character
            ]
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status' => 'active',
        ]);
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * LOGIN (email OR phone)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // User does not have an account
        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email. Please create an account first.'
            ], 404);
        }

        // Only normal users can use this login
        if ($user->role !== 'user') {
            return response()->json([
                'message' => 'Invalid user login.'
            ], 403);
        }

        // Check password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Incorrect email or password.'
            ], 401);
        }

        // Check if account is blocked
        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Account has been suspended. Please contact support to resolve the issue.'
            ], 403);
        }

        // Create authentication token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    /**
     * GET AUTH USER
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
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