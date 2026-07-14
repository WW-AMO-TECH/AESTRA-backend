<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;

class StateController extends Controller
{
    // GET STATES
    public function index()
    {
        try {
            $states = State::with('country')
                ->latest()
                ->get();
            return response()->json($states);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET SINGLE STATE
    public function show($id)
    {
        try {
            $state = State::with('country')
                ->findOrFail($id);

            return response()->json($state);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getByCountry($countryId)
    {
        $states = State::where('country_id', $countryId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $states
        ]);
    }

    // CREATE STATE
    public function store(Request $request)
    {
        try {
            $request->validate([
                'country_id' => 'required|exists:countries,id',
                'name' => 'required|string|max:255',
            ]);
            $state = State::create([
                'country_id' => $request->country_id,
                'name' => $request->name,
                'is_active' => $request->is_active ?? true,
            ]);
            return response()->json([
                'message' => 'State created successfully',
                'state' => $state,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE STATE
    public function update(Request $request, $id)
    {
        try {
            $state = State::findOrFail($id);
            $request->validate([
                'country_id' => 'required|exists:countries,id',
                'name' => 'required|string|max:255',
            ]);
            $state->update([
                'country_id' => $request->country_id,
                'name' => $request->name,
                'is_active' => $request->is_active ?? $state->is_active,
            ]);

            return response()->json([
                'message' => 'State updated successfully',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE STATE
    public function destroy($id)
    {
        try {
            $state = State::findOrFail($id);
            $state->delete();
            return response()->json([
                'message' => 'State deleted successfully',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET STATES BY COUNTRY
    public function getStates($countryId)
    {
        try {
            $states = State::where('country_id', $countryId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            return response()->json($states);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}