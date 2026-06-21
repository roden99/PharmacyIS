<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesAccount extends Model
{
    /** @use HasFactory<\Database\Factories\SalesAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'account_name',
        'status',
    ];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_sales_account')->withTimestamps();
    }
}
