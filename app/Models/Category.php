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
        'parent_id',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

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