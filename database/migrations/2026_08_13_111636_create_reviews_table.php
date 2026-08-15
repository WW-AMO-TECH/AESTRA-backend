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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Customer who wrote the review
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Product being reviewed
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Rating: 1 to 5 stars
            $table->unsignedTinyInteger('rating');

            // Review/comment
            $table->text('review');

            // Customer can edit only once
            $table->unsignedTinyInteger('edit_count')->default(0);

            // Admin moderation
            $table->boolean('is_approved')->default(false);

            $table->timestamps();

            // One customer can review a particular product only once
            $table->unique(['user_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};