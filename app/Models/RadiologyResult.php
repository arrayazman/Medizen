<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiologyResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'radiology_order_id',
        'doctor_id',
        'expertise',
        'waktu_hasil',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(RadiologyOrder::class, 'radiology_order_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}
