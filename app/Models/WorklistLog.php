<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorklistLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'status', 'request_payload', 'response_payload', 'error_message',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(RadiologyOrder::class, 'order_id');
    }
}
