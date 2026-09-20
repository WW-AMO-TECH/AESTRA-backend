<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SuperAdminSellerController extends Controller
{
    public function sellerRequests()
    {
        $sellers = User::where('role', 'seller')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json($sellers);
    }

    public function sellers(Request $request)
    {
        abort_unless(
            $request->user()->role === 'super_admin',
            403
        );

        return response()->json(
            User::where('role', 'seller')
                ->with('sellerVerification')
                ->withCount('products')
                ->latest()
                ->get()
        );
    }

    public function approveSeller(Request $request, User $user)
    {
        abort_unless(
            $request->user()->role === 'super_admin',
            403
        );

        abort_unless($user->role === 'seller', 404);

        $user->update([
            'status' => 'active',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Seller approved successfully.',
            'user' => $user,
        ]);
    }

    public function rejectSeller(Request $request, User $user)
    {
        abort_unless(
            $request->user()->role === 'super_admin',
            403
        );

        abort_unless($user->role === 'seller', 404);

        $user->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Seller rejected.',
        ]);
    }

    public function approveVerification(Request $request, User $user)
    {
        abort_unless(
            $request->user()->role === 'super_admin',
            403
        );

        abort_unless($user->role === 'seller', 404);

        $verification = $user->sellerVerification;

        if (!$verification) {
            return response()->json([
                'message' => 'Seller has not submitted verification.'
            ], 404);
        }

        $verification->update([
            'status' => 'verified',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $user->update([
            'verification_status' => 'verified',
        ]);

        return response()->json([
            'message' => 'Seller verification approved.',
            'user' => $user->fresh(),
            'verification' => $verification->fresh(),
        ]);
    }

    public function rejectVerification(Request $request, User $user)
    {
        abort_unless(
            $request->user()->role === 'super_admin',
            403
        );

        $data = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $verification = $user->sellerVerification;

        if (!$verification) {
            return response()->json([
                'message' => 'Verification not found.'
            ], 404);
        }

        $verification->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $data['reason'],
        ]);

        $user->update([
            'verification_status' => 'rejected',
        ]);

        return response()->json([
            'message' => 'Seller verification rejected.',
        ]);
    }

    public function deleteSeller(Request $request, User $user)
    {
        abort_unless(
            $request->user()->role === 'super_admin',
            403
        );

        abort_unless($user->role === 'seller', 404);

        $user->delete();

        return response()->json([
            'message' => 'Seller deleted successfully.',
        ]);
    }
}