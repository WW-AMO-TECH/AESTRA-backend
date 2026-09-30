<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryRate extends Model
{
    protected $fillable = [
        'pickup_location_id',
        'delivery_location_id',
        'delivery_type',
        'delivery_fee',
        'is_active',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function pickupLocation()
    {
        return $this->belongsTo(PickupLocation::class);
    }

    public function deliveryLocation()
    {
        return $this->belongsTo(DeliveryLocation::class);
    }
}