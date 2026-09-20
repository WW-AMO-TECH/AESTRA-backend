<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Product;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'store_name',
        'email',
        'phone',
        'password',
        'address',
        'business_address',
        'contact_information',
        'google_id',
        'avatar',
        'role',
        'status',
        'verification_status',
        'is_blocked',
        'approved_by',
        'approved_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function sellerVerification()
    {
        return $this->hasOne(SellerVerification::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isVerifiedSeller(): bool
    {
        return $this->role === 'seller'
            && $this->status === 'active'
            && $this->verification_status === 'verified';
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class, 'seller_id');
    }

    public function sellerPayouts()
    {
        return $this->hasMany(SellerPayout::class, 'seller_id');
    }
}
