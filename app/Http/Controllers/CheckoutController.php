<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([

            'fulfillment' => 'required|in:delivery,pickup',
            'payment_method' => 'required|in:card,transfer',

            'full_name' => 'required|string',
            'phone' => 'required|string',

            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'address' => 'nullable|string',

            'pickup_state' => 'nullable|string',
            'pickup_location' => 'nullable|string',
            'pickup_address' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $cart = Cart::with('product')
                ->where('user_id', auth()->id())
                ->get();

            if ($cart->count() === 0) {

                return response()->json([
                    'message' => 'Cart is empty'
                ], 400);
            }

            $subtotal = $cart->sum(function ($item) {

                return $item->product->price * $item->quantity;
            });

            $order = Order::create([

                'user_id' => auth()->id(),

                'order_number' => 'ORD-' . strtoupper(Str::random(10)),

                'fulfillment' => $request->fulfillment,

                'full_name' => $request->full_name,
                'phone' => $request->phone,

                'state' => $request->state,
                'city' => $request->city,
                'address' => $request->address,

                'pickup_state' => $request->pickup_state,
                'pickup_location' => $request->pickup_location,
                'pickup_address' => $request->pickup_address,

                'payment_method' => $request->payment_method,

                'payment_status' => 'paid',

                'status' => 'processing',

                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            foreach ($cart as $item) {

                OrderItem::create([

                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
            }

            // Clear cart
            Cart::where('user_id', auth()->id())->delete();

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Checkout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function confirmation($id)
    {
        $order = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($order);
    }
}