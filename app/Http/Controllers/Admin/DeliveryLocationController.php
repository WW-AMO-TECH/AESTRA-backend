<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use Illuminate\Http\Request;

class DeliveryLocationController extends Controller
{
    public function index()
    {
        $locations = DeliveryLocation::with(['country', 'state'])
            ->latest()
            ->get();

        return response()->json($locations);
    }

    public function show($id)
    {
        $location = DeliveryLocation::with(['country', 'state'])
            ->findOrFail($id);

        return response()->json($location);
    }

    public function getLocations($stateId)
    {
        return response()->json(
            DeliveryLocation::where('state_id', $stateId)
                ->with(['country', 'state'])
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'state_id' => ['required', 'exists:states,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $location = DeliveryLocation::create($validated);

        return response()->json([
            'message' => 'Delivery location created successfully.',
            'location' => $location->load(['country', 'state']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $location = DeliveryLocation::findOrFail($id);

        $validated = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'state_id' => ['required', 'exists:states,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $location->update($validated);

        return response()->json([
            'message' => 'Delivery location updated successfully.',
            'location' => $location->fresh()->load(['country', 'state']),
        ]);
    }

    public function toggleStatus($id)
    {
        $location = DeliveryLocation::findOrFail($id);

        $location->update([
            'is_active' => !$location->is_active,
        ]);

        return response()->json([
            'message' => $location->is_active
                ? 'Delivery location activated successfully.'
                : 'Delivery location deactivated successfully.',
            'location' => $location->fresh()->load(['country', 'state']),
        ]);
    }

    public function destroy($id)
    {
        $location = DeliveryLocation::findOrFail($id);

        $location->delete();

        return response()->json([
            'message' => 'Delivery location deleted successfully.',
        ]);
    }
}