<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarryItem extends Model
{
    protected $fillable = [
        'sales_agent_id',
        'carry_date',
        'reference_number',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function salesAgent()
    {
        return $this->belongsTo(SalesAgent::class);
    }

    public function details()
    {
        return $this->hasMany(CarryItemDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
