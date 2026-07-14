<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // GET CART
    public function index(Request $request)
    {
        try {

            $cart = Cart::with([
                'product.images',
                'product.brand',
            ])
            ->where('user_id', auth()->id())
            ->get()
            ->map(function ($item) {

                $product = $item->product;

                // FIRST PRODUCT IMAGE
                $image = null;

                if ($product && $product->images->count() > 0) {

                    $firstImage = $product->images->first();

                    if ($firstImage->image_url) {
                        $image = asset($firstImage->image_url);
                    } elseif ($firstImage->image) {
                        $image = asset('storage/' . $firstImage->image);
                    }
                }

                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,

                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'stock' => $product->stock,

                        'brand' => $product->brand?->name,

                        // IMAGE FOR FRONTEND
                        'image' => $image,

                        'images' => $product->images,
                    ],
                ];
            });

            return response()->json($cart);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ADD TO CART
    public function store(Request $request)
    {
        try {

            $request->validate([
                'product_id' => 'required|exists:products,id',
            ]);

            $cartItem = Cart::where('user_id', auth()->id())
                ->where('product_id', $request->product_id)
                ->first();

            if ($cartItem) {

                $cartItem->increment('quantity');

            } else {

                Cart::create([
                    'user_id' => auth()->id(),
                    'product_id' => $request->product_id,
                    'quantity' => 1,
                ]);
            }

            return response()->json([
                'message' => 'Added to cart',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE QUANTITY
    public function update(Request $request, $id)
    {
        try {

            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $cart = Cart::where('user_id', auth()->id())
                ->findOrFail($id);

            $cart->update([
                'quantity' => $request->quantity,
            ]);

            return response()->json([
                'message' => 'Updated',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // REMOVE ITEM
    public function destroy($id)
    {
        try {

            $cart = Cart::where('user_id', auth()->id())
                ->findOrFail($id);

            $cart->delete();

            return response()->json([
                'message' => 'Removed',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function clear()
    {
        Cart::where('user_id', auth()->id())->delete();

        return response()->json([
            'message' => 'Cart cleared successfully'
        ]);
    }
}