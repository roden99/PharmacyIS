<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'supplier_id',
        'invoice_no',
        'invoice_date',
        'delivery_date',
        'created_by',
        'updated_by',
        'invoice_date',
        'delivery_date'
    ];

    // protected $casts = [
    //     'invoice_date' => 'date',
    //     'delivery_date' => 'date',
    // ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryItem::class);
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
