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
        \Log::info('Signup route reached');

        return response()->json([
            'test' => true
        ]);

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
            'email' => 'required|email|exists:users,email', // email OR phone
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($user->role !== 'user') {
            return response()->json(['message' => 'Invalid user login'], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Incorrect password'
            ], 401);
        }

        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Account is blocked'
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