<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Radiographer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'sip_number', 'phone', 'email', 'is_active', 'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(RadiologyOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
