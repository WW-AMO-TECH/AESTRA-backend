<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'storage',
        'color',
        'ram',
        'original_price',
        'discount_percentage',
        'price',
        'stock',
        'weight',
        'status',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'discount_percentage' => 'integer',
        'stock' => 'integer',
        'status' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'variant_id')
            ->orderBy('sort_order');
    }

    /* Get final variant price after discount */
    public function getFinalPriceAttribute()
    {
        $price = (float) $this->price;
        $discount = (int) $this->discount_percentage;

        if ($discount <= 0) {
            return $price;
        }

        return $price - ($price * $discount / 100);
    }

    /* Check whether this variant is in stock */
    public function getInStockAttribute()
    {
        return $this->stock > 0;
    }

    /* Check whether this variant is active */
    public function getIsActiveAttribute()
    {
        return (bool) $this->status;
    }
}