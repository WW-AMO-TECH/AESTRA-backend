<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with([
            'brands',
            'parent',
            'children' => function ($query) {
                $query->with('brands')
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }
        ])
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function show($id)
    {
        $category = Category::with([
            'brands',
            'parent',
            'children' => function ($query) {
                $query->with('brands')
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }
        ])->findOrFail($id);

        return response()->json($category);
    }

    public function store(Request $request)
    {
        $request->merge([
            'parent_id' => $request->filled('parent_id')
                ? (int) $request->parent_id
                : null,
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($request) {
                    return $query->where('parent_id', $request->parent_id);
                }),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('categories', 'public');
        }

        $category = Category::create($validated);

        return response()->json([
            'message' => $validated['parent_id']
                ? 'Subcategory created successfully.'
                : 'Category created successfully.',
            'category' => $category->load('brands', 'parent', 'children'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->merge([
            'parent_id' => $request->filled('parent_id')
                ? (int) $request->parent_id
                : null,
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('parent_id', $request->parent_id);
                    })
                    ->ignore($category->id),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::notIn([$category->id]),
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $validated['image'] = $request->file('image')
                ->store('categories', 'public');
        }

        $category->update($validated);

        return response()->json([
            'message' => $validated['parent_id']
                ? 'Subcategory updated successfully.'
                : 'Category updated successfully.',
            'category' => $category->load('brands', 'parent', 'children'),
        ]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        $category->brands()->detach();

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}