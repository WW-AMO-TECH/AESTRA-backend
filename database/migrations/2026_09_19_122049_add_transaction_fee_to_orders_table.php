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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('transaction_fee', 10, 2)
                ->default(0)
                ->after('subtotal');

            $table->decimal('transaction_fee_percentage', 5, 2)
                ->default(0)
                ->after('transaction_fee');

            $table->string('bank_account_name')
                ->nullable()
                ->after('transaction_fee_percentage');

            $table->string('bank_transaction_number')
                ->nullable()
                ->after('bank_account_name');

            $table->index('bank_transaction_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['bank_transaction_number']);

            $table->dropColumn([
                'transaction_fee',
                'transaction_fee_percentage',
                'bank_account_name',
                'bank_transaction_number',
            ]);
        });
    }
};