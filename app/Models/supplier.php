<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'company',
        'lastname',
        'firstname',
        'middlename',
        'contact_email',
        'contact_phone',
        'address',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the user who created this supplier.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this supplier.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
