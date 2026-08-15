<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'review',
        'edit_count',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'edit_count' => 'integer',
        'status' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Customer who wrote the review
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Product being reviewed
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}