<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SuperAdminProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'category',
            'brand',
            'seller:id,name,store_name,phone,email,avatar',
            'images' => function ($query) {
                $query->whereNull('variant_id')->orderBy('sort_order');
            },
            'variants' => function ($query) {
                $query->orderBy('id');
            },
            'variants.images' => function ($query) {
                $query->orderBy('sort_order');
            },
        ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        switch ($request->get('sort')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;

            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;

            case 'newest':
            default:
                $query->latest();
                break;
        }

        $perPage = min(
            max((int) $request->get('per_page', 10), 1),
            50
        );

        $products = $query->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $product = Product::with([
            'category',
            'brand',
            'seller:id,name,store_name,phone,email,avatar',
            'images' => function ($query) {
                $query->whereNull('variant_id')->orderBy('sort_order');
            },
            'variants' => function ($query) {
                $query->orderBy('id');
            },
            'variants.images' => function ($query) {
                $query->orderBy('sort_order');
            },
        ])->findOrFail($id);

        return response()->json([
            'data' => $product,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'seller_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'seller');
                }),
            ],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'stock' => ['required', 'integer', 'min:0'],
            'model' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in(['Original', 'Refurbished'])],
            'color' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'ram' => ['nullable', 'string', 'max:255'],
            'battery' => ['nullable', 'string', 'max:255'],
            'storage' => ['nullable', 'string', 'max:255'],
            'camera' => ['nullable', 'string', 'max:255'],
            'cpu' => ['nullable', 'string', 'max:255'],
            'gpu' => ['nullable', 'string', 'max:255'],
            'display' => ['nullable', 'string', 'max:255'],
            'os' => ['nullable', 'string', 'max:255'],
            'connectivity' => ['nullable', 'string', 'max:255'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:255'],
            'is_flash_deal' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],

            'images' => ['nullable', 'array'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'variants' => ['nullable', 'array'],
            'variants.*.sku' => [
                'required',
                'string',
                'max:255',
                'distinct',
                'unique:product_variants,sku',
            ],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.color' => ['nullable', 'string', 'max:255'],
            'variants.*.storage' => ['nullable', 'string', 'max:255'],
            'variants.*.ram' => ['nullable', 'string', 'max:255'],
            'variants.*.original_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.discount_percentage' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.status' => ['nullable', 'boolean'],

            'variant_images' => ['nullable', 'array'],
            'variant_images.*' => ['nullable', 'array'],
            'variant_images.*.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        DB::beginTransaction();

        try {
            $slug = $validated['slug'] ?? Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;

            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $product = Product::create([
                'seller_id' => $validated['seller_id'],
                'sku' => $validated['sku'] ?? null,
                'slug' => $slug,
                'name' => $validated['name'],
                'original_price' => $validated['original_price'] ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],
                'brand_id' => $validated['brand_id'],
                'model' => $validated['model'] ?? null,
                'grade' => $validated['grade'] ?? null,
                'condition' => $validated['condition'],
                'stock' => $validated['stock'],
                'color' => $validated['color'] ?? null,
                'weight' => $validated['weight'] ?? null,
                'ram' => $validated['ram'] ?? null,
                'battery' => $validated['battery'] ?? null,
                'storage' => $validated['storage'] ?? null,
                'camera' => $validated['camera'] ?? null,
                'cpu' => $validated['cpu'] ?? null,
                'gpu' => $validated['gpu'] ?? null,
                'display' => $validated['display'] ?? null,
                'os' => $validated['os'] ?? null,
                'connectivity' => $validated['connectivity'] ?? null,
                'warranty' => $validated['warranty'] ?? null,
                'tag' => $validated['tag'] ?? null,
                'is_flash_deal' => $request->boolean('is_flash_deal'),
                'status' => $request->has('status')
                    ? $request->boolean('status')
                    : true,
                'description' => $validated['description'] ?? null,
            ]);

            if (!empty($validated['variants'])) {
                foreach ($validated['variants'] as $index => $variantData) {
                    $variant = $product->variants()->create([
                        'sku' => $variantData['sku'],
                        'name' => $variantData['name'] ?? null,
                        'color' => $variantData['color'] ?? null,
                        'storage' => $variantData['storage'] ?? null,
                        'ram' => $variantData['ram'] ?? null,
                        'original_price' => $variantData['original_price'] ?? null,
                        'discount_percentage' =>
                            $variantData['discount_percentage'] ?? 0,
                        'price' => $variantData['price'],
                        'stock' => $variantData['stock'],
                        'weight' => $variantData['weight'] ?? null,
                        'status' => isset($variantData['status'])
                            ? (bool) $variantData['status']
                            : true,
                    ]);

                    $variantFiles = $request->file(
                        "variant_images.$index",
                        []
                    );

                    foreach ($variantFiles as $imageIndex => $file) {
                        $path = $file->store(
                            'products/variants',
                            'public'
                        );

                        $variant->images()->create([
                            'product_id' => $product->id,
                            'image_url' => '/storage/' . $path,
                            'is_primary' => $imageIndex === 0,
                            'sort_order' => $imageIndex,
                        ]);
                    }
                }
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    $path = $file->store('products', 'public');

                    $product->images()->create([
                        'variant_id' => null,
                        'image_url' => '/storage/' . $path,
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $product->load([
                    'seller:id,name,store_name,phone,email,avatar',
                    'category',
                    'brand',
                    'images' => function ($query) {
                        $query->whereNull('variant_id')
                            ->orderBy('sort_order');
                    },
                    'variants',
                    'variants.images' => function ($query) {
                        $query->orderBy('sort_order');
                    },
                ]),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'seller_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'seller');
                }),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($product->id),
            ],
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'brand_id' => 'sometimes|exists:brands,id',
            'price' => 'sometimes|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'stock' => 'sometimes|integer|min:0',
            'condition' => [
                'nullable',
                Rule::in(['Original', 'Refurbished']),
            ],
            'grade' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'warranty' => 'nullable|string|max:255',
            'tag' => 'nullable|string|max:255',
            'is_flash_deal' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'ram' => 'nullable|string|max:255',
            'battery' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'camera' => 'nullable|string|max:255',
            'cpu' => 'nullable|string|max:255',
            'gpu' => 'nullable|string|max:255',
            'display' => 'nullable|string|max:255',
            'os' => 'nullable|string|max:255',
            'connectivity' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data = $request->only([
            'seller_id',
            'sku',
            'slug',
            'name',
            'original_price',
            'discount_percentage',
            'price',
            'category_id',
            'brand_id',
            'model',
            'grade',
            'condition',
            'stock',
            'color',
            'weight',
            'ram',
            'battery',
            'storage',
            'camera',
            'cpu',
            'gpu',
            'display',
            'os',
            'connectivity',
            'warranty',
            'tag',
            'description',
        ]);

        if ($request->has('is_flash_deal')) {
            $data['is_flash_deal'] = $request->boolean('is_flash_deal');
        }

        if ($request->has('status')) {
            $data['status'] = $request->boolean('status');
        }

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product->load([
                'seller:id,name,store_name,phone,email,avatar',
                'category',
                'brand',
                'images' => function ($query) {
                    $query->whereNull('variant_id')
                        ->orderBy('sort_order');
                },
                'variants',
                'variants.images' => function ($query) {
                    $query->orderBy('sort_order');
                },
            ]),
        ]);
    }

    public function destroy($id)
    {
        $product = Product::with([
            'images',
            'variants.images',
        ])->findOrFail($id);

        DB::beginTransaction();

        try {
            foreach ($product->images as $image) {
                $this->deleteStoredImage($image->image_url);
                $image->delete();
            }

            foreach ($product->variants as $variant) {
                foreach ($variant->images as $image) {
                    $this->deleteStoredImage($image->image_url);
                    $image->delete();
                }

                $variant->delete();
            }

            $product->delete();

            DB::commit();

            return response()->json([
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeVariant(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:255',
                'unique:product_variants,sku',
            ],
            'name' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'ram' => 'nullable|string|max:255',
            'original_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $variant = $product->variants()->create([
            'sku' => $validated['sku'],
            'name' => $validated['name'] ?? null,
            'color' => $validated['color'] ?? null,
            'storage' => $validated['storage'] ?? null,
            'ram' => $validated['ram'] ?? null,
            'original_price' => $validated['original_price'] ?? null,
            'discount_percentage' =>
                $validated['discount_percentage'] ?? 0,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'weight' => $validated['weight'] ?? null,
            'status' => $request->has('status')
                ? $request->boolean('status')
                : true,
        ]);

        return response()->json([
            'message' => 'Variant created successfully',
            'data' => $variant->load([
                'images' => function ($query) {
                    $query->orderBy('sort_order');
                },
            ]),
        ], 201);
    }

    public function updateVariant(
        Request $request,
        $productId,
        $variantId
    ) {
        $product = Product::findOrFail($productId);

        $variant = $product->variants()
            ->where('id', $variantId)
            ->firstOrFail();

        $request->validate([
            'sku' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')
                    ->ignore($variant->id),
            ],
            'name' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'ram' => 'nullable|string|max:255',
            'original_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'sku',
            'name',
            'color',
            'storage',
            'ram',
            'original_price',
            'discount_percentage',
            'price',
            'stock',
            'weight',
        ]);

        if ($request->has('status')) {
            $data['status'] = $request->boolean('status');
        }

        $variant->update($data);

        return response()->json([
            'message' => 'Variant updated successfully',
            'data' => $variant->load([
                'images' => function ($query) {
                    $query->orderBy('sort_order');
                },
            ]),
        ]);
    }

    public function destroyVariant($productId, $variantId)
    {
        $product = Product::findOrFail($productId);

        $variant = $product->variants()
            ->with('images')
            ->where('id', $variantId)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            foreach ($variant->images as $image) {
                $this->deleteStoredImage($image->image_url);
                $image->delete();
            }

            $variant->delete();

            DB::commit();

            return response()->json([
                'message' => 'Variant deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete variant',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadImages(Request $request, $productId)
    {
        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'primary_index' => 'nullable|integer|min:0',
            'sort_order' => ['nullable', 'array'],
            'sort_order.*' => 'nullable|integer|min:0',
        ]);

        $product = Product::findOrFail($productId);
        $uploadedImages = [];
        $primaryIndex = $request->input('primary_index');

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('products', 'public');

            $sortOrder = $request->input(
                "sort_order.$index",
                $index
            );

            $uploadedImage = ProductImage::create([
                'product_id' => $product->id,
                'variant_id' => null,
                'image_url' => '/storage/' . $path,
                'is_primary' => $primaryIndex !== null
                    ? (int) $primaryIndex === $index
                    : $index === 0,
                'sort_order' => $sortOrder,
            ]);

            $uploadedImages[] = $uploadedImage;
        }

        return response()->json([
            'message' => 'Product images uploaded successfully',
            'data' => $uploadedImages,
        ], 201);
    }

    public function uploadVariantImages(
        Request $request,
        $productId,
        $variantId
    ) {
        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'primary_index' => 'nullable|integer|min:0',
            'sort_order' => ['nullable', 'array'],
            'sort_order.*' => 'nullable|integer|min:0',
        ]);

        $product = Product::findOrFail($productId);

        $variant = $product->variants()
            ->where('id', $variantId)
            ->firstOrFail();

        $uploadedImages = [];
        $primaryIndex = $request->input('primary_index');

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store(
                'products/variants',
                'public'
            );

            $sortOrder = $request->input(
                "sort_order.$index",
                $index
            );

            $uploadedImage = ProductImage::create([
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'image_url' => '/storage/' . $path,
                'is_primary' => $primaryIndex !== null
                    ? (int) $primaryIndex === $index
                    : $index === 0,
                'sort_order' => $sortOrder,
            ]);

            $uploadedImages[] = $uploadedImage;
        }

        return response()->json([
            'message' => 'Variant images uploaded successfully',
            'data' => $uploadedImages,
        ], 201);
    }

    public function destroyImage($imageId)
    {
        $image = ProductImage::findOrFail($imageId);

        $this->deleteStoredImage($image->image_url);
        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully',
        ]);
    }

    public function setPrimaryImage($imageId)
    {
        $image = ProductImage::findOrFail($imageId);

        if ($image->variant_id) {
            ProductImage::where(
                'variant_id',
                $image->variant_id
            )->update([
                'is_primary' => false,
            ]);
        } else {
            ProductImage::where(
                'product_id',
                $image->product_id
            )
                ->whereNull('variant_id')
                ->update([
                    'is_primary' => false,
                ]);
        }

        $image->update([
            'is_primary' => true,
        ]);

        return response()->json([
            'message' => 'Primary image updated successfully',
            'data' => $image,
        ]);
    }

    public function updateImageOrder(
        Request $request,
        $imageId
    ) {
        $request->validate([
            'sort_order' => 'required|integer|min:0',
        ]);

        $image = ProductImage::findOrFail($imageId);

        $image->update([
            'sort_order' => $request->sort_order,
        ]);

        return response()->json([
            'message' => 'Image order updated successfully',
            'data' => $image,
        ]);
    }

    private function deleteStoredImage($imageUrl)
    {
        if (!$imageUrl) {
            return;
        }

        $path = str_replace('/storage/', '', $imageUrl);

        Storage::disk('public')->delete($path);
    }
}