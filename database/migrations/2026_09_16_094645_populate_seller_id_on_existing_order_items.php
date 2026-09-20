<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE order_items
            INNER JOIN products ON products.id = order_items.product_id
            SET order_items.seller_id = products.seller_id
            WHERE order_items.seller_id IS NULL
        ');
    }

    public function down(): void
    {
        DB::table('order_items')->update([
            'seller_id' => null,
        ]);
    }
};