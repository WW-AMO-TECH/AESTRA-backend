<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * Get all brands.
     */
    public function index()
    {
        $brands = Brand::with('categories')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($brands);
    }

    /* Get a single brand */
    public function show($id)
    {
        $brand = Brand::with('categories')->findOrFail($id);

        return response()->json($brand);
    }

    /* Create a brand. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'website' => 'nullable|url|max:255',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')
                ->store('brands', 'public');
        }

        $brand = Brand::create($validated);

        return response()->json([
            'message' => 'Brand created successfully.',
            'brand' => $brand->load('categories'),
        ], 201);
    }

    /* Update a brand. */
    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id,
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'website' => 'nullable|url|max:255',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('logo')) {

            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }

            $validated['logo'] = $request->file('logo')
                ->store('brands', 'public');
        }

        $brand->update($validated);

        return response()->json([
            'message' => 'Brand updated successfully.',
            'brand' => $brand->load('categories'),
        ]);
    }

    /* Delete a brand. */
    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);

        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        /* Remove brand/category relationships
           before deleting the brand. */
        $brand->categories()->detach();
        $brand->delete();

        return response()->json([
            'message' => 'Brand deleted successfully.',
        ]);
    }

    /* Assign categories to a brand. */
    public function updateCategories(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'category_ids' => 'array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $brand->categories()->sync(
            $validated['category_ids'] ?? []
        );

        return response()->json([
            'message' => 'Brand categories updated successfully.',
            'brand' => $brand->load('categories'),
        ]);
    }
}