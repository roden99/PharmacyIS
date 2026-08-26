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
        Schema::create('carry_item_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carry_item_id')->constrained('carry_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->decimal('quantity', 12, 4);
            $table->date('return_date');
            $table->unsignedBigInteger('returned_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carry_item_returns');
    }
};
