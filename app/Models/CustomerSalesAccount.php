<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSalesAccount extends Model
{
    protected $table = 'customer_sales_account';

    protected $fillable = [
        'customer_id',
        'sales_account_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesAccount()
    {
        return $this->belongsTo(SalesAccount::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }
}
