<?php

namespace App\Http\Controllers;

use App\Models\PickupLocation;
use Illuminate\Http\Request;

class PickupLocationController extends Controller
{
    public function index()
    {
        return PickupLocation::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'phone' => 'required|regex:/^[0-9]+$/|unique:users|min:11|max:14'
        ]);

        $location = PickupLocation::create([
            'name' => $request->name,
            'address' => $request->address,
            'state' => $request->state,
            'city' => $request->city,
            'phone' => $request->phone,
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'Pickup location created successfully',
            'data' => $location
        ]);
    }

    public function update(Request $request, $id)
    {
        $location = PickupLocation::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'address' => 'sometimes|string',
            'state' => 'sometimes|string',
            'city' => 'sometimes|string',
            'phone' => 'nullable|regex:/^[0-9]+$/'
        ]);

        $location->update([
            'name' => $request->name ?? $location->name,
            'address' => $request->address ?? $location->address,
            'state' => $request->state ?? $location->state,
            'city' => $request->city ?? $location->city,
            'phone' => $request->phone ?? $location->phone,
        ]);

        return response()->json([
            'message' => 'Pickup location updated successfully',
            'data' => $location
        ]);
    }

    public function destroy($id)
    {
        PickupLocation::destroy($id);

        return response()->json([
            'message' => 'Pickup location deleted successfully'
        ]);
    }
}