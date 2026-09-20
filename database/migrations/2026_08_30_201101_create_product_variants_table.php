<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            // Example: "128GB Black"
            $table->string('name')->nullable();
            // Variant-specific information
            $table->string('color')->nullable();
            $table->string('storage')->nullable();
            $table->string('ram')->nullable();

            // Variant-specific pricing
            $table->decimal('original_price', 10, 2)->nullable();
            $table->integer('discount_percentage')->default(0);
            $table->decimal('price', 10, 2);

            // Variant-specific inventory
            $table->integer('stock')->default(0);
            $table->decimal('weight', 8, 2)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

