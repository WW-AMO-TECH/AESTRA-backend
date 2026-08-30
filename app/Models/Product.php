<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Review;

class Product extends Model
{
    protected $fillable = [
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
        'is_flash_deal',
        'status',
        'description',
    ];

    /* TYPE CASTING */
    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'discount_percentage' => 'integer',
        'stock' => 'integer',
        'is_flash_deal' => 'boolean',
        'status' => 'boolean',
    ];

    // Brand
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Product Images (Gallery)
    public function images()
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order');
    }

    // Variants
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /* ACCESSORS (OPTIONAL BUT VERY USEFUL) */

    // Final price after discount
    public function getFinalPriceAttribute()
    {
        $price = (float) $this->price;
        $discount = (int) $this->discount_percentage;

        if ($discount <= 0) {
            return $price;
        }

        return $price - ($price * $discount / 100);
    }

    /* Check if product is active. */
    public function getIsActiveAttribute()
    {
        return (bool) $this->status;
    }

    // Stock status
    public function getInStockAttribute()
    {
        return $this->stock > 0;
    }

    // Wishlist relationship
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    // Reviews relationship
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}