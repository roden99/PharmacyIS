<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class strength extends Model
{
    /** @use HasFactory<\Database\Factories\StrengthFactory> */
    use HasFactory;

    protected $fillable = [
        'strengthname',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the user who created the strength.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the strength.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
