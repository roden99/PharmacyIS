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
        Schema::table('customer_sales_account', function (Blueprint $table) {
            // Drop FKs first (MySQL requires this before dropping composite PK)
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['sales_account_id']);
            // Drop composite PK and replace with unique constraint
            $table->dropPrimary(['customer_id', 'sales_account_id']);
            $table->unique(['customer_id', 'sales_account_id']);
            // Add auto-increment id as new primary key
            $table->id()->first();
            // Re-add FK constraints
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('sales_account_id')->references('id')->on('sales_accounts')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_sales_account', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['sales_account_id']);
            $table->dropUnique(['customer_id', 'sales_account_id']);
            $table->dropColumn('id');
            $table->primary(['customer_id', 'sales_account_id']);
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('sales_account_id')->references('id')->on('sales_accounts')->cascadeOnDelete();
        });
    }
};
