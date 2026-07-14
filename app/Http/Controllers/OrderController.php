<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{

    // POST /orders (checkout)
    public function store(Request $request)
    {
        $request->validate([
            'fulfillment' => 'required|string',
            'payment_method' => 'required|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required',
            'items.*.price' => 'required',
            'subtotal' => 'required|numeric',
        ]);

        $order = Order::create([
            'user_id' => $request->user()->id,
            'order_number' => 'ORD-' . time(),
            'reference' => $request->reference ?? null,

            'fulfillment' => $request->fulfillment,

            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'state' => $request->state,
            'city' => $request->city,
            'address' => $request->address,

            'pickup_state' => $request->pickup_state,
            'pickup_location' => $request->pickup_location,

            'payment_method' => $request->payment_method,

            'payment_status' => 'pending',
            'status' => 'pending',

            'subtotal' => $request->subtotal,
            'total' => $request->subtotal,
        ]);

        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        // Clear cart
        Cart::where('user_id', auth()->id())->delete();

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load('items.product.images')
        ]);
    }

    // GET /orders
    public function index()
    {
        $orders = Order::with('items.product.images')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($orders);
    }

    // GET /orders/{id}
    public function show($id)
    {
        $order = Order::with('items.product.images')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($order);
    }

    // PATCH /orders/{id}
    // public function updateStatus(Request $request, $id)
    // {
    //     $request->validate([
    //         'status' => 'required|string',
    //     ]);

    //     $order = Order::findOrFail($id);

    //     $order->status = $request->status;
    //     $order->save();

    //     return response()->json([
    //         'message' => 'Order updated successfully',
    //         'order' => $order,
    //     ]);
    // }

    // DELETE /admin/orders/{id}
    // public function destroy($id)
    // {
    //     $order = Order::findOrFail($id);

    //     $order->delete();

    //     return response()->json([
    //         'message' => 'Order deleted successfully',
    //     ]);
    // }
}