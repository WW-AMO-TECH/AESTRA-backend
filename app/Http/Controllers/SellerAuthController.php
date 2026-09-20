<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SellerAuthController extends Controller
{
    /**
     * SELLER SIGNUP / REQUEST
     */
    public function signup(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'store_name' => 'required|string|min:2|max:150',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|min:7|max:20|unique:users,phone',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
            'address' => 'required|string|max:500',
            'business_address' => 'required|string|max:500',
            'contact_information' => 'required|string|max:500',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'store_name' => $data['store_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'address' => $data['address'],
            'business_address' => $data['business_address'],
            'contact_information' => $data['contact_information'],
            'role' => 'seller',
            'status' => 'pending',
            'verification_status' => 'unverified',
            'is_blocked' => false,
        ]);

        return response()->json([
            'message' => 'Seller request submitted. Awaiting approval.',
            'user' => $user,
        ], 201);
    }

    /**
     * SELLER LOGIN
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !$user->password || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Incorrect email or password.'
            ], 401);
        }

        if ($user->role !== 'seller') {
            return response()->json([
                'message' => 'This account is not a seller account.'
            ], 403);
        }

        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Your account has been suspended.'
            ], 403);
        }

        if ($user->status === 'pending') {
            return response()->json([
                'message' => 'Your seller request is still awaiting approval.'
            ], 403);
        }

        if ($user->status === 'rejected') {
            return response()->json([
                'message' => 'Your seller request was rejected.'
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your seller account is not active.'
            ], 403);
        }

        $token = $user->createToken('seller_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Seller login successful.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * GET CURRENT SELLER
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        if ($user->role !== 'seller') {
            return response()->json([
                'message' => 'Access denied.'
            ], 403);
        }

        return response()->json($user);
    }

    /**
     * SELLER LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }
}