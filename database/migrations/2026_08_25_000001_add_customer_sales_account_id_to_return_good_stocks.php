<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_good_stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_sales_account_id')->nullable()->after('customer_id');
            $table->foreign('customer_sales_account_id', 'rgs_csa_fk')
                ->references('id')->on('customer_sales_account')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('return_good_stocks', function (Blueprint $table) {
            $table->dropForeign('rgs_csa_fk');
            $table->dropColumn('customer_sales_account_id');
        });
    }
};
