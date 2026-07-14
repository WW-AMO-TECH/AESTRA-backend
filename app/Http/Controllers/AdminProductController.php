<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    /**
     * GET ALL PRODUCTS
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'images']);

        // SEARCH
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // SORTING
        if ($request->sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        }
        if ($request->sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        }
        if ($request->sort === 'newest') {
            $query->latest();
        }
        if ($request->sort === 'popular') {
            $query->orderBy('views', 'desc');
        }

        // CATEGORY FILTER
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // BRAND FILTER
        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        // GRADE FILTER
        if ($request->grade) {
            $query->where('grade', $request->grade);
        }

        // CONDITION FILTER
        if ($request->condition) {
            $query->where('condition', $request->condition);
        }

        // PRICE RANGE FILTER
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }
        
        // PAGINATION
        $perPage = $request->get('per_page', 10);
        $products = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }

    /**
     * GET SINGLE PRODUCT
     */
    public function show($id)
    {
        $product = Product::with([
            'category',
            'brand',
            'images',
        ])->findOrFail($id);

        return response()->json($product);
    }

    /**
     * CREATE PRODUCT
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',

            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',

            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',

            'discount_percentage' => 'nullable|numeric|min:0|max:100',

            'stock' => 'required|integer|min:0',

            'condition' => 'required|string',
            'grade' => 'nullable|string',
            'model' => 'nullable|string',

            'warranty' => 'nullable|string',
            'tag' => 'nullable|string',
            'is_flash_deal' => 'nullable',

            'ram' => 'nullable|string',
            'battery' => 'nullable|string',
            'storage' => 'nullable|string',
            'camera' => 'nullable|string',
            'cpu' => 'nullable|string',
            'gpu' => 'nullable|string',
            'display' => 'nullable|string',
            'os' => 'nullable|string',
            'connectivity' => 'nullable|string',

            'description' => 'nullable|string',

            // IMAGES
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $product = Product::create([
            'name' => $validated['name'],

            'original_price' => $request->original_price,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'price' => $validated['price'],

            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'],
            'model' => $request->model,

            'grade' => $request->grade,
            'condition' => $validated['condition'],
            'stock' => $validated['stock'],

            'warranty' => $request->warranty,
            'tag' => $request->tag,
            'is_flash_deal' =>
                $request->is_flash_deal == "1" ||
                $request->is_flash_deal == true,

            'ram' => $request->ram,
            'battery' => $request->battery,
            'storage' => $request->storage,
            'camera' => $request->camera,
            'cpu' => $request->cpu,
            'gpu' => $request->gpu,
            'display' => $request->display,
            'os' => $request->os,
            'connectivity' => $request->connectivity,

            'description' => $request->description,
        ]);

        /**
         * UPLOAD IMAGES
         */
        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $file) {

                $path = $file->store('products', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product->load([
                'category',
                'brand',
                'images',
            ])
        ], 201);
    }

    /**
     * UPDATE PRODUCT
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',

            'category_id' => 'sometimes|exists:categories,id',
            'brand_id' => 'sometimes|exists:brands,id',

            'price' => 'sometimes|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',

            'discount_percentage' => 'nullable|numeric|min:0|max:100',

            'stock' => 'sometimes|integer|min:0',

            'condition' => 'nullable|string',
            'grade' => 'nullable|string',
            'model' => 'nullable|string',

            'warranty' => 'nullable|string',
            'tag' => 'nullable|string',
            'is_flash_deal' => 'nullable|boolean',

            'ram' => 'nullable|string',
            'battery' => 'nullable|string',
            'storage' => 'nullable|string',
            'camera' => 'nullable|string',
            'cpu' => 'nullable|string',
            'gpu' => 'nullable|string',
            'display' => 'nullable|string',
            'os' => 'nullable|string',
            'connectivity' => 'nullable|string',

            'description' => 'nullable|string',
        ]);

        $product->update([
            'name' => $request->name ?? $product->name,

            'price' =>
                $request->price ?? $product->price,

            'original_price' =>
                $request->original_price ?? $product->original_price,

            'discount_percentage' =>
                $request->discount_percentage ?? $product->discount_percentage,

            'category_id' =>
                $request->category_id ?? $product->category_id,

            'brand_id' =>
                $request->brand_id ?? $product->brand_id,

            'model' =>
                $request->model ?? $product->model,

            'condition' =>
                $request->condition ?? $product->condition,

            'grade' =>
                $request->grade ?? $product->grade,

            'stock' =>
                $request->stock ?? $product->stock,

            'ram' =>
                $request->ram ?? $product->ram,

            'battery' =>
                $request->battery ?? $product->battery,

            'storage' =>
                $request->storage ?? $product->storage,

            'camera' =>
                $request->camera ?? $product->camera,

            'cpu' =>
                $request->cpu ?? $product->cpu,

            'gpu' =>
                $request->gpu ?? $product->gpu,

            'display' =>
                $request->display ?? $product->display,

            'os' =>
                $request->os ?? $product->os,

            'connectivity' =>
                $request->connectivity ?? $product->connectivity,

            'warranty' =>
                $request->warranty ?? $product->warranty,

            'tag' =>
                $request->tag ?? $product->tag,

            'is_flash_deal' =>
                $request->is_flash_deal ?? $product->is_flash_deal,

            'description' =>
                $request->description ?? $product->description,
        ]);

        /**
         * OPTIONAL NEW IMAGE UPLOAD
         */
        if ($request->hasFile('images')) {

            foreach ($product->images as $oldImage) {
                Storage::disk('public')->delete(
                    str_replace('/storage/', '', $oldImage->image_url)
                );

                $oldImage->delete();
            }

            foreach ($request->file('images') as $image) {
                $path = $image->store(
                    'products',
                    'public'
                );

                $product->images()->create([
                    'image_url' => '/storage/' . $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product->load([
                'category',
                'brand',
                'images'
            ])
        ]);
    }

    /**
     * DELETE PRODUCT
     */
    public function destroy($id)
    {
        $product = Product::with('images')->findOrFail($id);

        /**
         * DELETE IMAGES FROM STORAGE
         */
        foreach ($product->images as $image) {

            $imagePath = str_replace(
                '/storage/',
                '',
                $image->image_url
            );

            Storage::disk('public')->delete($imagePath);

            $image->delete();
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * UPLOAD EXTRA PRODUCT IMAGES
     */
    public function uploadImages(Request $request, $productId)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $product = Product::findOrFail($productId);

        $uploadedImages = [];

        foreach ($request->file('images') as $image) {

            $path = $image->store('products', 'public');

            $uploadedImage = ProductImage::create([
                'product_id' => $product->id,
                'image_url' => '/storage/' . $path,
            ]);

            $uploadedImages[] = $uploadedImage;
        }

        return response()->json([
            'message' => 'Images uploaded successfully',
            'data' => $uploadedImages
        ]);
    }
}