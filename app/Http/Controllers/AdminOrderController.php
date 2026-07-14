<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class AdminOrderController extends Controller
{
    // Get all orders
    public function index()
    {
        return response()->json(
            Order::with(['user', 'items.product.images'])
                ->latest()
                ->get()
        );
    }

    // Get single order
    public function show($id)
    {
        $order = Order::with(['user', 'items.product.images'])
            ->findOrFail($id);

        return response()->json($order);
    }

    // Update order status / fulfillment
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|string',
            'fulfillment_status' => 'nullable|string',
        ]);

        $order->update($validated);

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order
        ]);
    }

    // Update order status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $order = Order::findOrFail($id);

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'message' => 'Status updated',
            'order' => $order
        ]);
    }

    // Delete order
    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }
}
