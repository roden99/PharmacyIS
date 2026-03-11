<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    /** @use HasFactory<\Database\Factories\ProductUnitFactory> */
    use HasFactory;

    protected $fillable = [
        'unit_name',
        'unit_code',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the products for the product unit.
     */
    public function products()
    {
        return $this->hasMany(product::class);
    }

    /**
     * Get the user who created the product unit.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the product unit.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
