<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    // GET COUNTRIES
    public function index()
    {
        try {
            $countries = Country::latest()->get();
            return response()->json($countries);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // CREATE COUNTRY
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:countries,name',
            ]);
            $country = Country::create([
                'name' => $request->name,
                'is_active' => $request->is_active ?? true,
            ]);
            return response()->json([
                'message' => 'Country created successfully',
                'country' => $country,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET SINGLE COUNTRY
    public function show($id)
    {
        try {
            $country = Country::findOrFail($id);
            return response()->json($country);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE COUNTRY
    public function update(Request $request, $id)
    {
        try {
            $country = Country::findOrFail($id);
            $request->validate([
                'name' => 'required|string|max:255|unique:countries,name,' . $id,
            ]);
            $country->update([
                'name' => $request->name,
                'is_active' => $request->is_active ?? $country->is_active,
            ]);
            return response()->json([
                'message' => 'Country updated successfully',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE COUNTRY
    public function destroy($id)
    {
        try {
            $country = Country::findOrFail($id);
            $country->delete();
            return response()->json([
                'message' => 'Country deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}