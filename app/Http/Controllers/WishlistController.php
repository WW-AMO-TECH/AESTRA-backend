<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlist = Wishlist::with('product.images')
            ->where('user_id', auth()->id())
            ->get();

        return response()->json($wishlist);
    }

    public function store($productId)
    {
        Wishlist::firstOrCreate([
            'user_id' => auth()->id(),
            'product_id' => $productId
        ]);

        return response()->json([
            'message' => 'Added to wishlist'
        ]);
    }

    public function destroy($productId)
    {
        Wishlist::where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->delete();

        return response()->json([
            'message' => 'Removed from wishlist'
        ]);
    }
}
