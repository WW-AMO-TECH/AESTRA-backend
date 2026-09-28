<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRate;
use Illuminate\Http\Request;

class DeliveryRateController extends Controller
{
    public function index()
    {
        $rates = DeliveryRate::with([
            'pickupLocation',
            'deliveryLocation',
        ])
            ->latest()
            ->get();

        return response()->json($rates);
    }

    public function show($id)
    {
        $rate = DeliveryRate::with([
            'pickupLocation',
            'deliveryLocation',
        ])->findOrFail($id);

        return response()->json($rate);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pickup_location_id' => [
                'required',
                'exists:pickup_locations,id',
            ],
            'delivery_location_id' => [
                'required',
                'exists:delivery_locations,id',
            ],
            'delivery_type' => [
                'required',
                'string',
                'max:100',
            ],
            'delivery_fee' => [
                'required',
                'numeric',
                'min:0',
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $exists = DeliveryRate::where('pickup_location_id', $validated['pickup_location_id'])
            ->where('delivery_location_id', $validated['delivery_location_id'])
            ->where('delivery_type', $validated['delivery_type'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A delivery rate already exists for this delivery location.',
            ], 422);
        }

        $rate = DeliveryRate::create($validated);

        return response()->json([
            'message' => 'Delivery rate created successfully.',
            'rate' => $rate->load([
                'pickupLocation',
                'deliveryLocation',
            ]),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $rate = DeliveryRate::findOrFail($id);

        $validated = $request->validate([
            'pickup_location_id' => [
                'required',
                'exists:pickup_locations,id',
            ],
            'delivery_location_id' => [
                'required',
                'exists:delivery_locations,id',
            ],
            'delivery_type' => [
                'required',
                'string',
                'max:100',
            ],
            'delivery_fee' => [
                'required',
                'numeric',
                'min:0',
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $exists = DeliveryRate::where('pickup_location_id', $validated['pickup_location_id'])
            ->where('delivery_location_id', $validated['delivery_location_id'])
            ->where('delivery_type', $validated['delivery_type'])
            ->where('id', '!=', $rate->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A delivery rate already exists for this pickup, delivery location and delivery type.',
            ], 422);
        }

        $rate->update($validated);

        return response()->json([
            'message' => 'Delivery rate updated successfully.',
            'rate' => $rate->fresh()->load([
                'pickupLocation',
                'deliveryLocation',
            ]),
        ]);
    }

    public function quote(Request $request)
    {
        $validated = $request->validate([
            'pickup_location_id' => [
                'required',
                'exists:pickup_locations,id',
            ],
            'delivery_location_id' => [
                'required',
                'exists:delivery_locations,id',
            ],
            'delivery_type' => [
                'required',
                'in:standard,express',
            ],
        ]);

        $rate = DeliveryRate::with([
            'pickupLocation',
            'deliveryLocation',
        ])
            ->where('pickup_location_id', $validated['pickup_location_id'])
            ->where('delivery_location_id', $validated['delivery_location_id'])
            ->where('delivery_type', $validated['delivery_type'])
            ->where('is_active', true)
            ->first();

        if (!$rate) {
            return response()->json([
                'message' => 'No delivery rate is available for this location and delivery type.',
            ], 404);
        }

        return response()->json([
            'id' => $rate->id,
            'pickup_location_id' => $rate->pickup_location_id,
            'delivery_location_id' => $rate->delivery_location_id,
            'delivery_type' => $rate->delivery_type,
            'delivery_fee' => $rate->delivery_fee,
            'pickup_location' => $rate->pickupLocation,
            'delivery_location' => $rate->deliveryLocation,
        ]);
    }

    public function toggleStatus($id)
    {
        $rate = DeliveryRate::findOrFail($id);

        $rate->update([
            'is_active' => !$rate->is_active,
        ]);

        return response()->json([
            'message' => $rate->is_active
                ? 'Delivery rate activated successfully.'
                : 'Delivery rate deactivated successfully.',
            'rate' => $rate->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $rate = DeliveryRate::findOrFail($id);

        $rate->delete();

        return response()->json([
            'message' => 'Delivery rate deleted successfully.',
        ]);
    }
}