<?php

namespace Database\Seeders;

use App\Models\WalletSetting;
use Illuminate\Database\Seeder;

class WalletSettingSeeder extends Seeder
{
    public function run(): void
    {
        WalletSetting::firstOrCreate([], [
            'commission_percentage' => 5.00,
        ]);
    }
}