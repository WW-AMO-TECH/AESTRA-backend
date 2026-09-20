<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'reference',
        'fulfillment',
        'full_name',
        'phone',
        'state',
        'city',
        'address',
        'pickup_state',
        'pickup_location',
        'payment_method',
        'payment_status',
        'status',
        'subtotal',
        'transaction_fee',
        'transaction_fee_percentage',
        'total',
        'bank_account_name',
        'bank_transaction_number',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'transaction_fee' => 'decimal:2',
        'transaction_fee_percentage' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function sellerPayouts()
    {
        return $this->hasMany(SellerPayout::class);
    }
}