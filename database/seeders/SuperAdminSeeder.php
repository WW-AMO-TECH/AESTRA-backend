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
                'name' => 'Richmond Chinelo Anne',
                'email' => 'richmondchineloanne@gmail.com',
                'phone' => '09034090272',
                'password' => Hash::make('Richmond#123'),

                'role' => 'super_admin',
                'status' => 'active',
                'is_blocked' => false,
            ]);
        }
    }
}
