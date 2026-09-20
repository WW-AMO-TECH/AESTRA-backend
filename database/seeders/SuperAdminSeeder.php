<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder 
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // prevent duplicate creation
        if (!User::where('role', 'super_admin')->exists()) {

            User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@gmail.com',
                'phone' => '09011112222',
                'password' => Hash::make('Superadmin#123'),
                'role' => 'super_admin',
                'status' => 'active',
                'verification_status' => 'verified',
                'is_blocked' => false,
            ]);
        }
    }
}
