<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column already exists
        if (!Schema::hasColumn('products', 'product_unit_id')) {
            Schema::table('products', function (Blueprint $table) {
                // Add column as nullable first to handle existing records
                $table->foreignId('product_unit_id')->nullable()->after('brand_id')->constrained('product_units')->onDelete('restrict');
            });
        }

        // Update existing products with a default unit (first unit in product_units table)
        $defaultUnitId = DB::table('product_units')->where('status', true)->first()?->id;

        if ($defaultUnitId) {
            DB::table('products')->whereNull('product_unit_id')->update(['product_unit_id' => $defaultUnitId]);
        }

        // Make the column required (if not already)
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_unit_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_unit_id']);
            $table->dropColumn('product_unit_id');
        });
    }
};
