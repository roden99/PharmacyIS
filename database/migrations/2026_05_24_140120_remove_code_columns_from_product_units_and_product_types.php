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
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropUnique(['unit_code']);
            $table->dropColumn('unit_code');
        });

        Schema::table('product_types', function (Blueprint $table) {
            $table->dropUnique(['type_code']);
            $table->dropColumn('type_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->string('unit_code')->nullable()->unique();
        });

        Schema::table('product_types', function (Blueprint $table) {
            $table->string('type_code')->nullable()->unique();
        });
    }
};
