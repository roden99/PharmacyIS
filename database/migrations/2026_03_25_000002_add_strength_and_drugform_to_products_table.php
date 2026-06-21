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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('strength_id')->nullable()->after('product_unit_id')->constrained('strengths')->onDelete('set null');
            $table->foreignId('drugform_id')->nullable()->after('strength_id')->constrained('drugforms')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['strength_id']);
            $table->dropForeignKeyIfExists(['drugform_id']);
            $table->dropColumn(['strength_id', 'drugform_id']);
        });
    }
};
