<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryItemFactory> */
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'product_id',
        'quantity_expected',
        'quantity_received',
        'warehouse_id',
        'created_by',
        'updated_by',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function product()
    {
        return $this->belongsTo(product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
