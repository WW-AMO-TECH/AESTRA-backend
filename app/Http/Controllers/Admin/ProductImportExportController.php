<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Support\Facades\DB;

class ProductImportExportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $file = $request->file('file');

        $tempPath = storage_path(
            'app/temp_import.' . $file->getClientOriginalExtension()
        );

        $file->move(dirname($tempPath), basename($tempPath));

        $rows = SimpleExcelReader::create($tempPath)->getRows();

        $imported = 0;
        $updated = 0;
        $failed = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Row 1 is the Excel header
                // Required fields
                if (
                    empty($row['sku']) ||
                    empty($row['name']) ||
                    empty($row['category']) ||
                    empty($row['brand']) ||
                    empty($row['price'])
                ) {

                    $failed[] = [
                        'row' => $rowNumber,
                        'reason' => 'Required fields are missing.'
                    ];

                    continue;
                }

                // Find Category
                $category = Category::where(
                    'name',
                    trim($row['category'])
                )->first();

                if (!$category) {

                    $failed[] = [
                        'row' => $rowNumber,
                        'reason' => "Category '{$row['category']}' not found."
                    ];

                    continue;
                }

                // Find Brand
                $brand = Brand::where(
                    'name',
                    trim($row['brand'])
                )->first();

                if (!$brand) {

                    $failed[] = [
                        'row' => $rowNumber,
                        'reason' => "Brand '{$row['brand']}' not found."
                    ];

                    continue;
                }

                // Check if SKU already exists
                $existing = Product::where(
                    'sku',
                    trim($row['sku'])
                )->first();

                $product = Product::updateOrCreate(

                    [
                        'sku' => trim($row['sku'])
                    ],

                    [
                        'name' => trim($row['name']),
                        'original_price' => is_numeric(trim($row['original_price'] ?? ''))
                        ? (float) trim($row['original_price'])
                        : null,
                        'discount_percentage' => is_numeric(trim($row['discount_percentage'] ?? ''))
                            ? (float) trim($row['discount_percentage'])
                            : 0,
                        'price' => is_numeric(trim($row['price'] ?? ''))
                            ? (float) trim($row['price'])
                            : 0,
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'model' => $row['model'] ?? null,
                        'grade' => $row['grade'] ?? null,
                        'condition' => $row['condition'] ?? 'Original',
                        'stock' => is_numeric(trim($row['stock'] ?? ''))
                        ? (int) trim($row['stock'])
                        : 0,
                        'ram' => $row['ram'] ?? null,
                        'battery' => $row['battery'] ?? null,
                        'storage' => $row['storage'] ?? null,
                        'camera' => $row['camera'] ?? null,
                        'cpu' => $row['cpu'] ?? null,
                        'gpu' => $row['gpu'] ?? null,
                        'display' => $row['display'] ?? null,
                        'os' => $row['os'] ?? null,
                        'connectivity' => $row['connectivity'] ?? null,
                        'warranty' => $row['warranty'] ?? null,
                        'tag' => $row['tag'] ?? null,
                        'is_flash_deal' => filter_var(
                            $row['flash_deal'] ?? false,
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'description' => $row['description'] ?? null,
                    ]

                );

                // Replace images
                $product->images()->delete();

                if (!empty($row['images'])) {
                    $images = array_filter(array_map('trim', explode(',', $row['images'])));
                    foreach ($images as $image) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_url' => $image,
                        ]);
                    }
                }

                if ($existing) {
                    $updated++;
                } else {
                    $imported++;
                }
            }

            DB::commit();

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully.',
                'imported' => $imported,
                'updated' => $updated,
                'failed' => count($failed),
                'errors' => $failed,
            ]);

        } catch (\Exception $e) {

            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Import failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function export()
    {
        $path = storage_path('app/products.csv');

        $writer = SimpleExcelWriter::create($path, 'csv');

        Product::with([
            'category',
            'brand',
            'images'
        ])->chunk(100, function($products) use($writer){

            foreach($products as $product){

                $writer->addRow([
                    'sku' => $product->sku,
                    'name'=>$product->name,
                    'original_price'=>$product->original_price,
                    'discount_percentage'=>$product->discount_percentage,
                    'price'=>$product->price,
                    'category'=>$product->category?->name,
                    'brand'=>$product->brand?->name,
                    'model'=>$product->model,
                    'grade'=>$product->grade,
                    'condition'=>$product->condition,
                    'stock'=>$product->stock,
                    'ram'=>$product->ram,
                    'storage'=>$product->storage,
                    'battery'=>$product->battery,
                    'camera'=>$product->camera,
                    'cpu'=>$product->cpu,
                    'gpu'=>$product->gpu,
                    'display'=>$product->display,
                    'os'=>$product->os,
                    'connectivity'=>$product->connectivity,
                    'warranty'=>$product->warranty,
                    'tag'=>$product->tag,
                    'flash_deal'=>$product->is_flash_deal,
                    'description'=>$product->description,
                    'images'=>$product
                        ->images
                        ->pluck('image_url')
                        ->implode(',')
                ]);

            }

        });

        $writer->close();

        return response()
                ->download($path)
                ->deleteFileAfterSend(true);
    }
}
