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
        Schema::table('sales_orders', function (Blueprint $table) {
            // Drop FKs first before dropping columns
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['sales_account_id']);
            $table->dropColumn(['customer_id', 'sales_account_id']);
            // Add FK to customer_sales_account
            $table->foreignId('customer_sales_account_id')
                ->after('id')
                ->constrained('customer_sales_account')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_sales_account_id']);
            $table->dropColumn('customer_sales_account_id');
            $table->foreignId('customer_id')->after('id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('sales_account_id')->nullable()->after('customer_id')->constrained('sales_accounts')->onDelete('set null');
        });
    }
};
