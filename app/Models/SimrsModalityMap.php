<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimrsModalityMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'kd_jenis_prw',
        'nm_perawatan',
        'modality_code',
        'examination_type_id',
        'notes',
    ];

    public function examinationType()
    {
        return $this->belongsTo(ExaminationType::class);
    }
}
