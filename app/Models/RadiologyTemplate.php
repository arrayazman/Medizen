<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiologyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_number',
        'examination_name',
        'expertise',
    ];
}
