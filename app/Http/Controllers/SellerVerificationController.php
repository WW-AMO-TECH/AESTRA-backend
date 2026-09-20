<?php

namespace App\Http\Controllers;

use App\Models\SellerVerification;
use Illuminate\Http\Request;

class SellerVerificationController extends Controller
{
    public function show(Request $request)
    {
        $seller = $request->user();

        abort_unless($seller->role === 'seller', 403);

        return response()->json([
            'seller' => $seller,
            'verification' => $seller->sellerVerification,
        ]);
    }

    public function submit(Request $request)
    {
        $seller = $request->user();

        abort_unless($seller->role === 'seller', 403);

        if ($seller->status !== 'active') {
            return response()->json([
                'message' => 'Your seller account must be approved before verification.'
            ], 403);
        }

        $data = $request->validate([
            'nin' => 'required|string|max:30',
            'bvn' => 'required|string|max:30',
            'business_registration_number' => 'required|string|max:100',
            'bank_name' => 'required|string|max:150',
            'account_name' => 'required|string|max:150',
            'account_number' => 'required|string|max:30',
        ]);

        $verification = SellerVerification::updateOrCreate(
            ['user_id' => $seller->id],
            [
                ...$data,
                'status' => 'pending',
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        $seller->update([
            'verification_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Verification submitted successfully. Awaiting review.',
            'verification' => $verification,
        ]);
    }
}