<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pickup_location_id')
                ->constrained('pickup_locations')
                ->cascadeOnDelete();

            $table->foreignId('delivery_location_id')
                ->constrained('delivery_locations')
                ->cascadeOnDelete();

            $table->string('delivery_type');

            $table->decimal('delivery_fee', 10, 2);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                [
                    'pickup_location_id',
                    'delivery_location_id',
                    'delivery_type',
                ],
                'pickup_delivery_type_rate_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_rates');
    }
};