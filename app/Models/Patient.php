<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'no_rm',
        'nik',
        'nama',
        'jenis_kelamin',
        'tgl_lahir',
        'alamat',
        'no_hp',
        'gol_darah',
        'tempat_lahir',
        'pendidikan',
        'nama_ibu',
        'png_jawab',
        'nama_pj',
        'pekerjaan_pj',
        'suku_bangsa',
        'bahasa',
        'cacat_fisik',
        'is_tni',
        'tni_golongan',
        'tni_kesatuan',
        'tni_pangkat',
        'tni_jabatan',
        'is_polri',
        'polri_golongan',
        'polri_kesatuan',
        'polri_pangkat',
        'polri_jabatan',
        'agama',
        'status_nikah',
        'asuransi',
        'no_peserta',
        'email',
        'tgl_daftar',
        'pekerjaan',
        'kelurahan',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'alamat_pj',
        'kelurahan_pj',
        'kecamatan_pj',
        'kabupaten_pj',
        'provinsi_pj',
        'instansi_pasien',
        'nip_nrp',
        'satusehat_id',
    ];

    protected $casts = [
        'tgl_lahir' => 'date',
        'tgl_daftar' => 'date',
        'is_tni' => 'boolean',
        'is_polri' => 'boolean',
    ];

    public function getDetailedAgeAttribute(): array
    {
        if (!$this->tgl_lahir)
            return ['y' => 0, 'm' => 0, 'd' => 0];
        $birthDate = \Carbon\Carbon::parse($this->tgl_lahir);
        $diff = $birthDate->diff(now());
        return [
            'y' => $diff->y,
            'm' => $diff->m,
            'd' => $diff->d
        ];
    }

    public function orders()
    {
        return $this->hasMany(RadiologyOrder::class);
    }

    public function getUmurAttribute(): string
    {
        if (!$this->tgl_lahir)
            return '-';
        return \Carbon\Carbon::parse($this->tgl_lahir)->age . ' tahun';
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('no_rm', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%")
                ->orWhere('nama', 'like', "%{$search}%");
        });
    }
}
