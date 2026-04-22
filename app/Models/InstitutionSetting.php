<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_name',
        'address',
        'phone',
        'email',
        'website',
        'logo_path',
        'watermark_path',
        'footer_path',
        'pacs_license_code',
        'satusehat_organization_id',
        'satusehat_client_id',
        'satusehat_client_secret',
        'satusehat_env',
        'app_license_key',
        'license_signature',
        'display_gallery_id',
        'display_instructions',
        'display_marquee_text',
    ];

    protected $casts = [
        'is_pro' => 'boolean',
        'display_instructions' => 'array',
    ];

    public function displayGallery()
    {
        return $this->belongsTo(Gallery::class, 'display_gallery_id');
    }
}
