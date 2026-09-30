<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupLocation;
use Illuminate\Http\Request;

class PickupLocationController extends Controller
{
    public function index()
    {
        return response()->json(
            PickupLocation::with(['country', 'state'])->get()
        );
    }

    public function show($id)
    {
        return response()->json(
            PickupLocation::with(['country', 'state'])->findOrFail($id)
        );
    }

    public function getLocations($stateId)
    {
        return response()->json(
            PickupLocation::where('state_id', $stateId)
                ->with(['country', 'state'])
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|regex:/^[0-9]+$/|min:11|max:14',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_active' => 'sometimes|boolean',
        ]);

        $location = new PickupLocation();

        $location->country_id = $validated['country_id'];
        $location->state_id = $validated['state_id'];
        $location->name = $validated['name'];
        $location->address = $validated['address'];
        $location->phone = $validated['phone'];
        $location->opening_time = $validated['opening_time'] ?? null;
        $location->closing_time = $validated['closing_time'] ?? null;
        $location->latitude = $validated['latitude'];
        $location->longitude = $validated['longitude'];
        $location->is_active = $validated['is_active'] ?? true;

        $location->save();

        return response()->json([
            'message' => 'Pickup location created successfully',
            'data' => $location->fresh()->load(['country', 'state']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $location = PickupLocation::findOrFail($id);

        $validated = $request->validate([
            'country_id' => 'sometimes|exists:countries,id',
            'state_id' => 'sometimes|exists:states,id',
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|regex:/^[0-9]+$/|min:11|max:14',
            'opening_time' => 'sometimes|nullable|date_format:H:i',
            'closing_time' => 'sometimes|nullable|date_format:H:i',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'is_active' => 'sometimes|boolean',
        ]);

        if (array_key_exists('country_id', $validated)) {
            $location->country_id = $validated['country_id'];
        }

        if (array_key_exists('state_id', $validated)) {
            $location->state_id = $validated['state_id'];
        }

        if (array_key_exists('name', $validated)) {
            $location->name = $validated['name'];
        }

        if (array_key_exists('address', $validated)) {
            $location->address = $validated['address'];
        }

        if (array_key_exists('phone', $validated)) {
            $location->phone = $validated['phone'];
        }

        if (array_key_exists('opening_time', $validated)) {
            $location->opening_time = $validated['opening_time'];
        }

        if (array_key_exists('closing_time', $validated)) {
            $location->closing_time = $validated['closing_time'];
        }

        if (array_key_exists('latitude', $validated)) {
            $location->latitude = $validated['latitude'];
        }

        if (array_key_exists('longitude', $validated)) {
            $location->longitude = $validated['longitude'];
        }

        if (array_key_exists('is_active', $validated)) {
            $location->is_active = $validated['is_active'];
        }

        $location->save();

        return response()->json([
            'message' => 'Pickup location updated successfully',
            'data' => $location->fresh()->load(['country', 'state']),
        ]);
    }

    public function toggleStatus($id)
    {
        $location = PickupLocation::findOrFail($id);

        $location->is_active = !$location->is_active;
        $location->save();

        return response()->json([
            'message' => 'Pickup location status updated successfully',
            'data' => $location->fresh()->load(['country', 'state']),
        ]);
    }

    public function destroy($id)
    {
        $location = PickupLocation::findOrFail($id);

        $location->delete();

        return response()->json([
            'message' => 'Pickup location deleted successfully',
        ]);
    }
}