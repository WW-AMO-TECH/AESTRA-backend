<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('name');
            $table->boolean('status')->default(true)->after('is_flash_deal');
            $table->string('color')->nullable()->after('model');
            $table->decimal('weight', 8, 2)->nullable()->after('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropUnique(['slug']);

            $table->dropColumn([
                'slug',
                'status',
                'color',
                'weight',
            ]);
        });
    }
};
