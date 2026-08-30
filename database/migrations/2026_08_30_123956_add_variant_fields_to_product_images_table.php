<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignId('variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->boolean('is_primary')
                ->default(false)
                ->after('image_url');

            $table->integer('sort_order')
                ->default(0)
                ->after('is_primary');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);

            $table->dropColumn([
                'variant_id',
                'is_primary',
                'sort_order',
            ]);
        });
    }
};