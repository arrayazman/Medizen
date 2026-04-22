<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiologyReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'hasil',
        'kesimpulan',
        'dokter_id',
        'validated_at',
        'digital_signature_path',
        'status',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'DRAFT';
    const STATUS_FINAL = 'FINAL';
    const STATUS_VALIDATED = 'VALIDATED';

    public function order()
    {
        return $this->belongsTo(RadiologyOrder::class, 'order_id');
    }

    public function dokter()
    {
        return $this->belongsTo(Doctor::class, 'dokter_id');
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED && $this->validated_at !== null;
    }
}
