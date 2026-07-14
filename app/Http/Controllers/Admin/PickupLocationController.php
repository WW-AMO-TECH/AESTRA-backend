<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupLocation;
use Illuminate\Http\Request;

class PickupLocationController extends Controller
{
    // GET PICKUP LOCATIONS
    public function index()
    {
        try {
            $locations = PickupLocation::with([
                'country',
                'state'
            ])
            ->latest()
            ->get();
            return response()->json($locations);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET SINGLE PICKUP LOCATION
    public function show($id)
    {
        try {
            $location = PickupLocation::with([
                'country',
                'state'
            ])
            ->findOrFail($id);

            return response()->json($location);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // CREATE PICKUP LOCATION
    public function store(Request $request)
    {
        try {
            $request->validate([
                'country_id' => 'required|exists:countries,id',
                'state_id' => 'required|exists:states,id',
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'phone' => 'nullable|string|max:40',
                'opening_time' => 'required',
                'closing_time' => 'required',
            ]);

            $location = PickupLocation::create([
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
                'opening_time' => $request->opening_time,
                'closing_time' => $request->closing_time,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'message' => 'Pickup location created successfully',
                'location' => $location,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE PICKUP LOCATION
    public function update(Request $request, $id)
    {
        try {
            $location = PickupLocation::findOrFail($id);
            $request->validate([
                'country_id' => 'required|exists:countries,id',
                'state_id' => 'required|exists:states,id',
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'phone' => 'nullable|string|max:40',
                'opening_time' => 'required',
                'closing_time' => 'required',
            ]);

            $location->update([
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
                'opening_time' => $request->opening_time,
                'closing_time' => $request->closing_time,
                'is_active' => $request->is_active ?? $location->is_active,
            ]);

            return response()->json([
                'message' => 'Pickup location updated successfully',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE PICKUP LOCATION
    public function destroy($id)
    {
        try {
            $location = PickupLocation::findOrFail($id);
            $location->delete();
            return response()->json([
                'message' => 'Pickup location deleted successfully',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET LOCATIONS BY STATE
    public function getLocations($stateId)
    {
        try {
            $locations = PickupLocation::where('state_id', $stateId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            return response()->json($locations);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}