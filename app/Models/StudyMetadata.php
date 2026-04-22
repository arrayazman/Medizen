<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyMetadata extends Model
{
    use HasFactory;

    protected $table = 'study_metadata';

    protected $fillable = [
        'order_id', 'study_uid', 'PACS_id', 'series_count',
        'instance_count', 'study_date', 'description', 'patient_name',
        'raw_metadata',
    ];

    protected $casts = [
        'study_date' => 'date',
        'raw_metadata' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(RadiologyOrder::class, 'order_id');
    }
}

