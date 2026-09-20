<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    // ==================== SUPER ADMIN AUTH ====================

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (
            !$user ||
            !$user->password ||
            !Hash::check($data['password'], $user->password)
        ) {
            return response()->json([
                'message' => 'Incorrect email or password.'
            ], 401);
        }

        if ($user->role !== 'super_admin') {
            return response()->json([
                'message' => 'You are not allowed to access the Super Admin login.'
            ], 403);
        }

        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Account has been suspended.'
            ], 403);
        }

        $token = $user->createToken('super_admin_auth_token')
            ->plainTextToken;

        return response()->json([
            'message' => 'Super Admin login successful.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function signup(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'super_admin',
            'status' => 'active',
            'verification_status' => 'verified',
            'is_blocked' => false,
        ]);

        return response()->json([
            'message' => 'Super Admin created successfully.',
            'user' => $user,
        ], 201);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        if ($user->role !== 'super_admin') {
            return response()->json([
                'message' => 'Access denied.'
            ], 403);
        }

        return response()->json([
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }


    // ==================== SELLER MANAGEMENT ====================

    // GET ALL PENDING SELLER SIGNUP REQUESTS
    public function sellerRequests()
    {
        $sellers = User::where('role', 'seller')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json($sellers);
    }


    // ==================== CUSTOMER MANAGEMENT ====================

    // GET ALL USERS
    public function users(Request $request)
    {
        $query = User::where('role', 'user')
            ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $totalCustomers = User::where('role', 'user')
            ->where('status', 'active')
            ->count();

        $blockedCustomers = User::where('role', 'user')
            ->where('status', 'active')
            ->where('is_blocked', true)
            ->count();

        $activeCustomers = User::where('role', 'user')
            ->where('status', 'active')
            ->where('is_blocked', false)
            ->count();

        $newThisMonth = User::where('role', 'user')
            ->where('status', 'active')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $users = $query
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->latest()
            ->paginate(8);

        return response()->json([
            'users' => $users,
            'statistics' => [
                'total' => $totalCustomers,
                'active' => $activeCustomers,
                'blocked' => $blockedCustomers,
                'new_this_month' => $newThisMonth,
            ],
        ]);
    }

    // GET USER ORDERS
    public function userOrders($id)
    {
        $user = User::where('role', 'user')
            ->findOrFail($id);

        $orders = $user->orders()
            ->with('items.product.images', 'items.product.seller')
            ->latest()
            ->get();

        return response()->json([
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    // BLOCK USER
    public function blockUser($id)
    {
        $user = User::where('role', 'user')->findOrFail($id);

        $user->is_blocked = true;
        $user->save();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'User blocked successfully.'
        ]);
    }

    // UNBLOCK USER
    public function unblockUser($id)
    {
        $user = User::findOrFail($id);

        $user->is_blocked = false;
        $user->save();

        return response()->json([
            'message' => 'User has been unblocked successfully.'
        ]);
    }

    // DELETE USER
    public function deleteUser($id)
    {
        $user = User::where('role', 'user')
            ->findOrFail($id);

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.'
        ]);
    }
}