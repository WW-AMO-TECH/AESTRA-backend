<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Products belonging to this brand.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Categories belonging to this brand.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}