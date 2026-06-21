<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'company',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'address',
        'status',
        'is_drugstore',
    ];

    public function salesAccounts()
    {
        return $this->belongsToMany(SalesAccount::class, 'customer_sales_account')->withTimestamps();
    }
}
