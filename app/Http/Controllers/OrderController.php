<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.product.images')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::with('items.product.images')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($order);
    }
}