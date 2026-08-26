<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarryItemReturn extends Model
{
    protected $fillable = [
        'carry_item_id',
        'product_id',
        'lot_id',
        'quantity',
        'return_date',
        'returned_by',
    ];

    public function carryItem()
    {
        return $this->belongsTo(CarryItem::class);
    }

    public function product()
    {
        return $this->belongsTo(product::class);
    }
}
