<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'products'
            AND TABLE_SCHEMA = DATABASE()
            AND COLUMN_NAME = 'product_unit_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (empty($exists)) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('product_unit_id')->references('id')->on('product_units')->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_unit_id']);
        });
    }
};
