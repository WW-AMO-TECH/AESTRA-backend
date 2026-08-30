<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Brands that use this category.
     */
    public function brands()
    {
        return $this->belongsToMany(Brand::class);
    }

    /**
     * Products belonging to this category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}