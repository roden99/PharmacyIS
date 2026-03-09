<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseItem extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseItemFactory> */
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the warehouse that owns the item.
     */
    public function warehouse()
    {
        return $this->belongsTo(warehouse::class);
    }

    /**
     * Get the product associated with the item.
     */
    public function product()
    {
        return $this->belongsTo(product::class);
    }

    /**
     * Get the user who created the item.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the item.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
