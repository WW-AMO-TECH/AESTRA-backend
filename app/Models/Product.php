<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /*
    |--------------------------------------------------------------------------
    | MASS ASSIGNMENT
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'sku',
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
        'description',
    ];

    /*
    |--------------------------------------------------------------------------
    | TYPE CASTING
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_flash_deal' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Brand
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // Product Images (Gallery)
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (OPTIONAL BUT VERY USEFUL)
    |--------------------------------------------------------------------------
    */

    // Final price after discount
    public function getFinalPriceAttribute()
    {
        if ($this->discount_percentage > 0) {
            return $this->price - ($this->price * $this->discount_percentage / 100);
        }

        return $this->price;
    }

    // Stock status
    public function getInStockAttribute()
    {
        return $this->stock > 0;
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
}