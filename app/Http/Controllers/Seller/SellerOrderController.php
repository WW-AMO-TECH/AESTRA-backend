<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class SellerOrderController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = $request->user()->id;

        $orders = Order::whereHas('items', function ($query) use ($sellerId) {
            $query->where('seller_id', $sellerId);
        })
        ->with([
            'user',
            'items' => function ($query) use ($sellerId) {
                $query->where('seller_id', $sellerId)
                    ->with([
                        'product.images',
                        'seller',
                    ]);
            },
        ])
        ->latest()
        ->get();

        return response()->json($orders);
    }

    public function show(Request $request, $id)
    {
        $sellerId = $request->user()->id;

        $order = Order::where('id', $id)
            ->whereHas('items', function ($query) use ($sellerId) {
                $query->where('seller_id', $sellerId);
            })
            ->with([
                'user',
                'items' => function ($query) use ($sellerId) {
                    $query->where('seller_id', $sellerId)
                        ->with([
                            'product.images',
                            'seller',
                        ]);
                },
            ])
            ->firstOrFail();

        return response()->json($order);
    }
}