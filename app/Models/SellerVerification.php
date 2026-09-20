<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerVerification extends Model
{
    protected $fillable = [
        'user_id',
        'nin',
        'bvn',
        'business_registration_number',
        'bank_name',
        'account_name',
        'account_number',
        'identity_document',
        'business_document',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $hidden = [
        'nin',
        'bvn',
        'account_number',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}