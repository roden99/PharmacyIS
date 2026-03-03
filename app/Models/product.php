<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'isgeneric',
        'productname',
        'brand_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'isgeneric' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Get the brand that the product belongs to.
     */
    public function brand()
    {
        return $this->belongsTo(brand::class);
    }

    /**
     * Get the user who created the product.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the product.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
