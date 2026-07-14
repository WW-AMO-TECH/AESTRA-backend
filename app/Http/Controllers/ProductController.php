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
     * Amazon-style filtering + pagination
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['category', 'brand', 'images']);

        /* -----------------------------
        | NORMALIZE INPUTS (IMPORTANT)
        ------------------------------*/
        $search   = trim($request->get('search', ''));
        $category = $request->get('category');
        $brand    = $request->get('brand');
        $grade    = $request->get('grade');
        $sort     = $request->get('sort', 'relevance');
        $perPage  = (int) $request->get('per_page', 12);

        $perPage = $perPage > 48 ? 48 : $perPage; // prevent abuse

        /* -----------------------------
        | AMAZON-STYLE SEARCH
        | (fast + flexible)
        ------------------------------*/
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        /* -----------------------------
        | FILTERS (SAFE CASTING)
        ------------------------------*/
        if (!empty($category)) {
            $query->where('category_id', (int) $category);
        }

        if (!empty($brand)) {
            $query->where('brand_id', (int) $brand);
        }

        if (!empty($grade)) {
            $query->where('grade', $grade);
        }

        /* -----------------------------
        | AMAZON-STYLE SORTING
        ------------------------------*/
        match ($sort) {
            'price-asc'  => $query->orderBy('price', 'asc'),
            'price-desc' => $query->orderBy('price', 'desc'),
            'newest'     => $query->orderByDesc('created_at'),
            default      => $query->orderByDesc('created_at'), // relevance fallback
        };

        /* -----------------------------
        | PAGINATION (CRITICAL)
        ------------------------------*/
        $products = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $products->items(),

            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
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
            'images'
        ])->find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'original_price' => $product->original_price,
                'stock' => $product->stock,
                'condition' => $product->condition,
                'warranty' => $product->warranty,

                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,

                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,

                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_url' => $image->image_url
                            ? asset($image->image_url)
                            : null,
                    ];
                }),

                // SPECS
                'ram' => $product->ram,
                'battery' => $product->battery,
                'storage' => $product->storage,
                'camera' => $product->camera,
                'cpu' => $product->cpu,
                'gpu' => $product->gpu,
                'display' => $product->display,
                'os' => $product->os,
                'connectivity' => $product->connectivity,
            ]
        ]);
    }

    /**
     * 🔥 SAFE JSON ARRAY HANDLER
     */
    private function safeJsonArray($data)
    {
        if (!$data) return [];

        if (is_array($data)) return $data;

        $decoded = json_decode($data, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 🔥 SAFE JSON OBJECT HANDLER
     */
    private function safeJsonObject($data)
    {
        if (!$data) return [];

        if (is_array($data)) return $data;

        $decoded = json_decode($data, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * GET /api/products/meta
     */
    public function meta()
    {
        return response()->json([
            'categories' => Category::select('id', 'name')->get(),
            'brands' => Brand::select('id', 'name')->get(),
            'grades' => ['New', 'A', 'B', 'C'],
        ]);
    }
}