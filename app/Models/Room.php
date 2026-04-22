<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'modality_id', 'floor', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
