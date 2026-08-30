<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /* GET /api/categories */
    public function categories()
    {
        return response()->json(
            Category::select('id', 'name')->get()
        );
    }

    /* GET /api/brands */
    public function brands()
    {
        return response()->json(
            Brand::select('id', 'name')->get()
        );
    }

    /* GET /api/products */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'category',
                'brand',

                // Main product images
                'images' => function ($query) {
                    $query->orderBy('sort_order');
                },

                // Variants + their images
                'variants.images' => function ($query) {
                    $query->orderBy('sort_order');
                },
            ])
            ->withAvg([
                'reviews as rating' => function ($query) {
                    $query->where('status', 'approved');
                }
            ], 'rating')
            ->withCount([
                'reviews as reviews_count' => function ($query) {
                    $query->where('status', 'approved');
                }
            ]);

        /* -----------------------------
        | NORMALIZE INPUTS
        ------------------------------*/

        $search = trim($request->get('search', ''));
        $category = $request->get('category');
        $brand = $request->get('brand');
        $grade = $request->get('grade');
        $condition = $request->get('condition');

        $sort = $request->get('sort', 'relevance');
        $perPage = (int) $request->get('per_page', 12);

        $perPage = $perPage > 48 ? 48 : $perPage;

        /* -----------------------------
        | SEARCH
        ------------------------------*/

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%")
                    ->orWhere('model', 'LIKE', "%{$search}%");
            });
        }

        /* -----------------------------
        | CATEGORY
        ------------------------------*/

        if (!empty($category)) {
            $query->where('category_id', (int) $category);
        }

        /* -----------------------------
        | BRAND
        ------------------------------*/

        if (!empty($brand)) {
            $query->where('brand_id', (int) $brand);
        }

        /* -----------------------------
        | GRADE
        ------------------------------*/

        if (!empty($grade)) {
            $query->where('grade', $grade);
        }

        /* -----------------------------
        | CONDITION
        ------------------------------*/

        if (!empty($condition)) {
            $query->where('condition', $condition);
        }

        /* -----------------------------
        | FLASH DEALS
        ------------------------------*/

        if ($request->has('is_flash_deal')) {

            $isFlashDeal = filter_var(
                $request->get('is_flash_deal'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if (
                $request->get('is_flash_deal') === '1' ||
                $request->get('is_flash_deal') === 1 ||
                $isFlashDeal === true
            ) {
                $query->where('is_flash_deal', true);
            }
        }

        /* -----------------------------
        | ACTIVE PRODUCTS ONLY
        ------------------------------*/

        $query->where('status', true);

        /* -----------------------------
        | SORTING
        ------------------------------*/

        match ($sort) {

            'price-asc' =>
                $query->orderBy('price', 'asc'),

            'price-desc' =>
                $query->orderBy('price', 'desc'),

            'rating' =>
                $query->orderByDesc('rating'),

            'newest' =>
                $query->orderByDesc('created_at'),

            default =>
                $query->orderByDesc('created_at'),
        };

        /* -----------------------------
        | PAGINATION
        ------------------------------*/

        $products = $query
            ->paginate($perPage)
            ->withQueryString();

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

    /* GET /api/products/{id} */
    public function show($id)
    {
        $product = Product::with([
            'brand',
            'category',

            // Main product images
            'images' => function ($query) {
                $query->orderBy('sort_order');
            },

            // Variants + variant images
            'variants' => function ($query) {
                $query->where('status', true);
            },

            'variants.images' => function ($query) {
                $query->orderBy('sort_order');
            },
        ])
        ->withAvg([
            'reviews as rating' => function ($query) {
                $query->where('status', 'approved');
            }
        ], 'rating')
        ->withCount([
            'reviews as reviews_count' => function ($query) {
                $query->where('status', 'approved');
            }
        ])
        ->find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'name' => $product->name,
                'model' => $product->model,
                'description' => $product->description,
                'status' => $product->status,
                'tag' => $product->tag,
                'is_flash_deal' => $product->is_flash_deal,

                'price' => $product->price,
                'original_price' => $product->original_price,
                'discount_percentage' => $product->discount_percentage,
                'final_price' => $product->final_price,

                'stock' => $product->stock,
                'in_stock' => $product->in_stock,

                'grade' => $product->grade,
                'condition' => $product->condition,
                'color' => $product->color,
                'weight' => $product->weight,
                'warranty' => $product->warranty,

                'ram' => $product->ram,
                'battery' => $product->battery,
                'storage' => $product->storage,
                'camera' => $product->camera,
                'cpu' => $product->cpu,
                'gpu' => $product->gpu,
                'display' => $product->display,
                'os' => $product->os,
                'connectivity' => $product->connectivity,

                'rating' => $product->rating
                    ? round((float) $product->rating, 1)
                    : null,

                'reviews_count' => $product->reviews_count ?? 0,

                'brand' => $product->brand
                    ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                    ]
                    : null,

                'category' => $product->category
                    ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ]
                    : null,

                /* MAIN PRODUCT IMAGES */

                'images' => $product->images
                    ->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_url' => $image->image_url
                                ? asset($image->image_url)
                                : null,
                            'variant_id' => $image->variant_id,
                            'is_primary' => $image->is_primary,
                            'sort_order' => $image->sort_order,
                        ];
                    })
                    ->values(),

                /* PRODUCT VARIANTS */

                'variants' => $product->variants
                    ->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'product_id' => $variant->product_id,
                            'sku' => $variant->sku,
                            'name' => $variant->name,
                            'storage' => $variant->storage,
                            'color' => $variant->color,
                            'ram' => $variant->ram,
                            'original_price' => $variant->original_price,
                            'discount_percentage' =>
                                $variant->discount_percentage,
                            'price' => $variant->price,
                            'stock' => $variant->stock,
                            'weight' => $variant->weight,
                            'status' => $variant->status,
                            'images' => $variant->images
                                ->map(function ($image) {
                                    return [
                                        'id' => $image->id,

                                        'image_url' =>
                                            $image->image_url
                                                ? asset(
                                                    $image->image_url
                                                )
                                                : null,

                                        'variant_id' =>
                                            $image->variant_id,

                                        'is_primary' =>
                                            $image->is_primary,

                                        'sort_order' =>
                                            $image->sort_order,
                                    ];
                                })
                                ->values(),
                        ];
                    })
                    ->values(),
            ]
        ]);
    }

    /* GET /api/products/meta */
    public function meta()
    {
        return response()->json([

            'categories' => Category::select(
                'id',
                'name'
            )->get(),

            'brands' => Brand::select(
                'id',
                'name'
            )->get(),

            'grades' => [
                'New',
                'A',
                'B',
                'C'
            ],
        ]);
    }
}