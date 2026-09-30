<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('pickup_location_id')
                ->nullable()
                ->after('pickup_location')
                ->constrained('pickup_locations')
                ->nullOnDelete();

            $table->foreignId('delivery_location_id')
                ->nullable()
                ->after('pickup_location_id')
                ->constrained('delivery_locations')
                ->nullOnDelete();

            $table->string('delivery_type')
                ->nullable()
                ->after('delivery_location_id');

            $table->decimal('delivery_fee', 10, 2)
                ->default(0)
                ->after('delivery_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pickup_location_id']);
            $table->dropForeign(['delivery_location_id']);

            $table->dropColumn([
                'pickup_location_id',
                'delivery_location_id',
                'delivery_type',
                'delivery_fee',
            ]);
        });
    }
};