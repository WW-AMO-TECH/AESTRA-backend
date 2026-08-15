<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Get all approved reviews for a product.
     */
    public function index($productId)
    {
        $product = Product::findOrFail($productId);

        $reviews = Review::with('user:id,name')
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->latest()
            ->get();

        $totalReviews = $reviews->count();

        $averageRating = $totalReviews > 0
            ? round($reviews->avg('rating'), 1)
            : 0;

        $ratingBreakdown = [
            5 => $reviews->where('rating', 5)->count(),
            4 => $reviews->where('rating', 4)->count(),
            3 => $reviews->where('rating', 3)->count(),
            2 => $reviews->where('rating', 2)->count(),
            1 => $reviews->where('rating', 1)->count(),
        ];

        return response()->json([
            'reviews' => $reviews,
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'rating_breakdown' => $ratingBreakdown,
        ]);
    }

    /**
     * Submit a new review.
     */
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'You must be logged in to submit a review.'
            ], 401);
        }

        // Check if customer has already reviewed this product
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this product.'
            ], 409);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'edit_count' => 0,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Review submitted successfully, awaiting admin approval.',
            'review' => $review->load('user:id,name'),
        ], 201);
    }

    /**
     * Customer edits their review.
     * Customer is allowed ONE edit only.
     */
    public function update(Request $request, $reviewId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'You must be logged in.'
            ], 401);
        }

        $review = Review::findOrFail($reviewId);

        // Make sure this review belongs to the logged-in customer
        if ($review->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not allowed to edit this review.'
            ], 403);
        }

        // Customer can only edit once
        if ($review->edit_count >= 1) {
            return response()->json([
                'message' => 'You have already used your one allowed edit.'
            ], 403);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $review->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'edit_count' => 1,

            // Edited reviews go back for moderation
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Review updated successfully. It is awaiting admin approval again.',
            'review' => $review->load('user:id,name'),
        ]);
    }

    /**
     * Delete customer's own review.
     */
    public function destroy($reviewId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'You must be logged in.'
            ], 401);
        }

        $review = Review::findOrFail($reviewId);

        if ($review->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not allowed to delete this review.'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully.'
        ]);
    }

    /* Get all reviews for admin */
    public function adminIndex(Request $request)
    {
        $reviews = Review::with([
            'user:id,name,email',
            'product:id,name'
        ])
        ->latest()
        ->get();

        return response()->json([
            'reviews' => $reviews,
        ]);
    }

    /* Get a single review for admin */
    public function adminShow($reviewId)
    {
        $review = Review::with([
            'user:id,name,email',
            'product:id,name'
        ])->findOrFail($reviewId);

        return response()->json([
            'review' => $review,
        ]);
    }

    /* Admin can edit a review unlimited times */
    public function adminUpdate(Request $request, $reviewId)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $review = Review::findOrFail($reviewId);

        $review->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'],
        ]);

        return response()->json([
            'message' => 'Review updated successfully by admin.',
            'review' => $review->load([
                'user:id,name,email',
                'product:id,name'
            ]),
        ]);
    }

    /* Approve a review */
    public function approve($reviewId)
    {
        $review = Review::findOrFail($reviewId);

        $review->update([
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'Review approved successfully.',
            'review' => $review,
        ]);
    }

    /* Reject a review */
    public function reject($reviewId)
    {
        $review = Review::findOrFail($reviewId);

        $review->update([
            'status' => 'rejected',
        ]);

        return response()->json([
            'message' => 'Review rejected successfully.',
            'review' => $review,
        ]);
    }

    /* Admin deletes any review */
    public function adminDestroy($reviewId)
    {
        $review = Review::findOrFail($reviewId);

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully by admin.',
        ]);
    }
}