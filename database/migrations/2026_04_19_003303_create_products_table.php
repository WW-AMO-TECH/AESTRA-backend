<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();
            // sku is a unique identifier for each product, used for inventory management and tracking.
            // $table->string('sku')->unique();
            $table->string('name');
            $table->decimal('original_price', 10, 2)->nullable();
            $table->integer('discount_percentage')->default(0);
            $table->decimal('price', 10, 2);
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('model')->nullable();
            $table->string('grade')->nullable();
            $table->enum('condition', [ 'Original', 'Refurbished'])->default('Original');
            $table->integer('stock')->default(0);
            $table->string('ram')->nullable();
            $table->string('battery')->nullable();
            $table->string('storage')->nullable();
            $table->string('camera')->nullable();
            $table->string('cpu')->nullable();
            $table->string('gpu')->nullable();
            $table->string('display')->nullable();
            $table->string('os')->nullable();
            $table->string('connectivity')->nullable();
            $table->string('warranty')->nullable();
            $table->string('tag')->nullable();
            $table->boolean('is_flash_deal')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};