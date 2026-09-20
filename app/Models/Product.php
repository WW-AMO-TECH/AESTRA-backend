<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Review;

class Product extends Model
{
    protected $fillable = [
        'seller_id',
        'name',
        'sku',
        'slug',
        'original_price',
        'discount_percentage',
        'price',
        'category_id',
        'brand_id',
        'model',
        'color',
        'grade',
        'condition',
        'stock',
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

    protected $casts = [
        'original_price' => 'decimal:2',
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'discount_percentage' => 'integer',
        'stock' => 'integer',
        'is_flash_deal' => 'boolean',
        'status' => 'boolean',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFinalPriceAttribute()
    {
        return (float) $this->price;
    }

    public function getIsActiveAttribute()
    {
        return (bool) $this->status;
    }

    public function getInStockAttribute()
    {
        return $this->stock > 0;
    }
}