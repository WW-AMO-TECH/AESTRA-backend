<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSignupRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'is_blocked'
    ];
}
