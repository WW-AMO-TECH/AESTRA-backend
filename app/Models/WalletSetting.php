<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletSetting extends Model
{
    protected $fillable = [
        'commission_percentage',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
    ];
}