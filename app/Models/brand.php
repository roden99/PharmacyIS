<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class brand extends Model
{
    /** @use HasFactory<\Database\Factories\BrandFactory> */
    use HasFactory;

    protected $fillable = [
        'brandname',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the products for the brand.
     */
    public function products()
    {
        return $this->hasMany(product::class);
    }

    /**
     * Get the user who created the brand.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the brand.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
