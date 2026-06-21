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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('supplier_id')->after('purchase_order_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $afterColumn = 'invoice_date';

        if (Schema::hasColumn('deliveries', 'invoice_no')) {
            $afterColumn = 'invoice_no';
        } elseif (Schema::hasColumn('deliveries', 'delivery_no')) {
            $afterColumn = 'delivery_no';
        }

        Schema::table('deliveries', function (Blueprint $table) use ($afterColumn) {
            $table->foreignId('supplier_id')->after($afterColumn)->change();
        });
    }
};
