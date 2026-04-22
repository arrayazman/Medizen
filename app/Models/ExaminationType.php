<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'modality_id',
        'code',
        'name',
        'description',
        'duration_minutes',
        'is_active',
        'price',
        'js_rs',
        'paket_bhp',
        'jm_dokter',
        'jm_petugas',
        'jm_perujuk',
        'kso',
        'manajemen',
        'jenis_bayar_kd',
        'jenis_bayar_nama',
        'kelas',
        'satusehat_periksa_code',
        'satusehat_periksa_system',
        'satusehat_periksa_display',
        'satusehat_sampel_code',
        'satusehat_sampel_system',
        'satusehat_sampel_display',
        'simrs_code',
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

    public function satusehatMapping()
    {
        return $this->hasOne(SatusehatMapping::class);
    }
}
