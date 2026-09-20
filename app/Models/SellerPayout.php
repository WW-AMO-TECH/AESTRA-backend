<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerPayout extends Model
{
    protected $fillable = [
        'seller_id',
        'order_id',
        'gross_amount',
        'commission_amount',
        'payout_amount',
        'status',
        'reference',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}