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
        'product_unit_id',
        'product_type_id',
        'strength_id',
        'drugform_id',
        'status',
        'product_qty',
        'reorder_level',
        'initial_date',
        'initial_qty',
        'is_inventory',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'isgeneric'    => 'boolean',
        'status'       => 'boolean',
        'is_inventory' => 'boolean',
    ];

    /**
     * Get the brand that the product belongs to.
     */
    public function brand()
    {
        return $this->belongsTo(brand::class);
    }

    /**
     * Get the unit that the product belongs to.
     */
    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    /**
     * Get the strength that the product belongs to.
     */
    public function strength()
    {
        return $this->belongsTo(strength::class, 'strength_id');
    }

    /**
     * Get the drug form that the product belongs to.
     */
    public function drugform()
    {
        return $this->belongsTo(drugform::class, 'drugform_id');
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
