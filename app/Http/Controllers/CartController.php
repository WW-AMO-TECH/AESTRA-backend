<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
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

                if (!$product) {
                    return null;
                }

                $image = null;

                if ($product->images->count() > 0) {
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
                        'original_price' => $product->original_price,
                        'discount_percentage' => $product->discount_percentage,
                        'price' => $product->price,
                        'final_price' => $product->final_price,
                        'stock' => $product->stock,
                        'brand' => $product->brand?->name,
                        'image' => $image,
                        'images' => $product->images,
                    ],
                ];
            })
            ->filter()
            ->values();

            return response()->json($cart);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'nullable|integer|min:1',
            ]);

            $product = \App\Models\Product::where('id', $request->product_id)
                ->where('status', true)
                ->first();

            if (!$product) {
                return response()->json([
                    'message' => 'Product is unavailable.',
                ], 422);
            }

            $quantity = (int) ($request->quantity ?? 1);

            $cartItem = Cart::where('user_id', auth()->id())
                ->where('product_id', $product->id)
                ->first();

            $newQuantity = ($cartItem?->quantity ?? 0) + $quantity;

            if ($newQuantity > $product->stock) {
                return response()->json([
                    'message' => "Only {$product->stock} item(s) are currently available.",
                ], 422);
            }

            if ($cartItem) {
                $cartItem->update([
                    'quantity' => $newQuantity,
                ]);
            } else {
                Cart::create([
                    'user_id' => auth()->id(),
                    'product_id' => $product->id,
                    'quantity' => $quantity,
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

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $cart = Cart::with('product')
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            if (!$cart->product || !$cart->product->status) {
                return response()->json([
                    'message' => 'This product is no longer available.',
                ], 422);
            }

            if ($request->quantity > $cart->product->stock) {
                return response()->json([
                    'message' => "Only {$cart->product->stock} item(s) are currently available.",
                ], 422);
            }

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
            'message' => 'Cart cleared successfully',
        ]);
    }
}