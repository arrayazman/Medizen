<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'specialization',
        'sip_number',
        'phone',
        'email',
        'is_active',
        'user_id',
        'kd_dokter',
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
        return $this->hasMany(RadiologyOrder::class, 'referring_doctor_id');
    }

    public function reports()
    {
        return $this->hasMany(RadiologyReport::class, 'dokter_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
