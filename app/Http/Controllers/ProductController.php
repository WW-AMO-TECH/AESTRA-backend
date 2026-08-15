<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/categories
     */
    public function categories()
    {
        return response()->json(
            Category::select('id', 'name')->get()
        );
    }

    /**
     * GET /api/brands
     */
    public function brands()
    {
        return response()->json(
            Brand::select('id', 'name')->get()
        );
    }

    /**
     * GET /api/products
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'category',
                'brand',
                'images',
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

        $search   = trim($request->get('search', ''));
        $category = $request->get('category');
        $brand    = $request->get('brand');
        $grade    = $request->get('grade');
        $condition = $request->get('condition');

        $sort     = $request->get('sort', 'relevance');
        $perPage  = (int) $request->get('per_page', 12);

        $perPage = $perPage > 48 ? 48 : $perPage;

        /* -----------------------------
        | SEARCH
        ------------------------------*/

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {

                $q->where(
                    'name',
                    'LIKE',
                    "%{$search}%"
                )
                ->orWhere(
                    'description',
                    'LIKE',
                    "%{$search}%"
                );

            });
        }

        /* -----------------------------
        | CATEGORY
        ------------------------------*/

        if (!empty($category)) {
            $query->where(
                'category_id',
                (int) $category
            );
        }

        /* -----------------------------
        | BRAND
        ------------------------------*/

        if (!empty($brand)) {
            $query->where(
                'brand_id',
                (int) $brand
            );
        }

        /* -----------------------------
        | GRADE
        ------------------------------*/

        if (!empty($grade)) {
            $query->where(
                'grade',
                $grade
            );
        }

        /* -----------------------------
        | CONDITION
        ------------------------------*/

        if (!empty($condition)) {
            $query->where(
                'condition',
                $condition
            );
        }

        /* -----------------------------
        | FLASH DEALS
        ------------------------------*/

        if ($request->has('is_flash_deal')) {

            $isFlashDeal =
                filter_var(
                    $request->get('is_flash_deal'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

            if (
                $request->get('is_flash_deal') === '1' ||
                $request->get('is_flash_deal') === 1 ||
                $isFlashDeal === true
            ) {
                $query->where(
                    'is_flash_deal',
                    true
                );
            }

        }

        /* -----------------------------
        | SORTING
        ------------------------------*/

        match ($sort) {

            'price-asc' =>
                $query->orderBy(
                    'price',
                    'asc'
                ),

            'price-desc' =>
                $query->orderBy(
                    'price',
                    'desc'
                ),

            'rating' =>
                $query->orderByDesc(
                    'rating'
                ),

            'newest' =>
                $query->orderByDesc(
                    'created_at'
                ),

            default =>
                $query->orderByDesc(
                    'created_at'
                ),
        };

        /* -----------------------------
        | PAGINATION
        ------------------------------*/

        $products =
            $query
                ->paginate($perPage)
                ->withQueryString();

        return response()->json([
            'data' => $products->items(),

            'meta' => [
                'current_page' =>
                    $products->currentPage(),

                'last_page' =>
                    $products->lastPage(),

                'per_page' =>
                    $products->perPage(),

                'total' =>
                    $products->total(),
            ],
        ]);
    }

    /**
     * GET /api/products/{id}
     */
    public function show($id)
    {
        $product = Product::with([
            'brand',
            'category',
            'images',
        ])
        ->withAvg([
            'reviews as rating' => function ($query) {
                $query->where(
                    'status',
                    'approved'
                );
            }
        ], 'rating')
        ->withCount([
            'reviews as reviews_count' => function ($query) {
                $query->where(
                    'status',
                    'approved'
                );
            }
        ])
        ->find($id);

        if (!$product) {

            return response()->json([
                'message' =>
                    'Product not found'
            ], 404);

        }

        return response()->json([
            'data' => [

                'id' =>
                    $product->id,

                'name' =>
                    $product->name,

                'description' =>
                    $product->description,

                'price' =>
                    $product->price,

                'original_price' =>
                    $product->original_price,

                'stock' =>
                    $product->stock,

                'condition' =>
                    $product->condition,

                'warranty' =>
                    $product->warranty,

                /*
                |--------------------------------------------------------------------------
                | RATING
                |--------------------------------------------------------------------------
                */

                'rating' =>
                    $product->rating
                        ? round(
                            (float) $product->rating,
                            1
                        )
                        : null,

                'reviews_count' =>
                    $product->reviews_count ?? 0,

                'brand' =>
                    $product->brand
                        ? [
                            'id' =>
                                $product->brand->id,

                            'name' =>
                                $product->brand->name,
                        ]
                        : null,

                'category' =>
                    $product->category
                        ? [
                            'id' =>
                                $product->category->id,

                            'name' =>
                                $product->category->name,
                        ]
                        : null,

                'images' =>
                    $product->images->map(
                        function ($image) {

                            return [
                                'id' =>
                                    $image->id,

                                'image_url' =>
                                    $image->image_url
                                        ? asset(
                                            $image->image_url
                                        )
                                        : null,
                            ];

                        }
                    ),

                /* SPECS */

                'ram' =>
                    $product->ram,

                'battery' =>
                    $product->battery,

                'storage' =>
                    $product->storage,

                'camera' =>
                    $product->camera,

                'cpu' =>
                    $product->cpu,

                'gpu' =>
                    $product->gpu,

                'display' =>
                    $product->display,

                'os' =>
                    $product->os,

                'connectivity' =>
                    $product->connectivity,
            ]
        ]);
    }

    /**
     * SAFE JSON ARRAY HANDLER
     */
    private function safeJsonArray($data)
    {
        if (!$data) {
            return [];
        }

        if (is_array($data)) {
            return $data;
        }

        $decoded =
            json_decode(
                $data,
                true
            );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    /**
     * SAFE JSON OBJECT HANDLER
     */
    private function safeJsonObject($data)
    {
        if (!$data) {
            return [];
        }

        if (is_array($data)) {
            return $data;
        }

        $decoded =
            json_decode(
                $data,
                true
            );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    /**
     * GET /api/products/meta
     */
    public function meta()
    {
        return response()->json([

            'categories' =>
                Category::select(
                    'id',
                    'name'
                )->get(),

            'brands' =>
                Brand::select(
                    'id',
                    'name'
                )->get(),

            'grades' => ['New', 'A', 'B', 'C'],
        ]);
    }
}