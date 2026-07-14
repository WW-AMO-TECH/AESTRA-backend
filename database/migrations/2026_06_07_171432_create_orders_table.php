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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('reference')->nullable()->unique();

            // Delivery or Pickup
            $table->enum('fulfillment', ['delivery','pickup']);

            // Customer
            $table->string('full_name');
            $table->string('phone');

            // Delivery
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();

            // Pickup
            $table->string('pickup_state')->nullable();
            $table->string('pickup_location')->nullable();

            // Payment
            $table->enum('payment_method', ['paystack','bank_transfer']);

            $table->enum('payment_status', ['pending','paid','failed'])->default('pending');

            // Order status
            $table->enum('status', ['pending','processing','delivered','ready_for_pickup','picked_up','completed','cancelled'])->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
