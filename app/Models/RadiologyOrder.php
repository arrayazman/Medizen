<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiologyOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'accession_number',
        'patient_id',
        'modality',
        'examination_type_id',
        'referring_doctor_id',
        'radiographer_id',
        'study_instance_uid',
        'scheduled_date',
        'scheduled_time',
        'station_ae_title',
        'procedure_description',
        'clinical_info',
        'priority',
        'status',
        'room_id',
        'created_by',
        'notes',
        'waktu_sample',
        'waktu_mulai_periksa',
        'patient_portal_token',
        'satusehat_service_request_id',
        'satusehat_imaging_study_id',
        'satusehat_encounter_id',
        'satusehat_sent_at',
        'origin_system',
        'external_id',
        'simrs_no_rawat',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'satusehat_sent_at' => 'datetime',
    ];

    // Status constants
    const STATUS_ORDERED = 'ORDERED';
    const STATUS_WAITING_SAMPLE = 'WAITING_SAMPLE';
    const STATUS_SAMPLE_TAKEN = 'SAMPLE_TAKEN';
    const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_REPORTED = 'REPORTED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_SENT_TO_PACS = 'SENT_TO_PACS';
    const STATUS_VALIDATED = 'VALIDATED';

    public static function statuses(): array
    {
        return [
            self::STATUS_ORDERED,
            self::STATUS_WAITING_SAMPLE,
            self::STATUS_SAMPLE_TAKEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_REPORTED,
            self::STATUS_CANCELLED,
            self::STATUS_SENT_TO_PACS,
            self::STATUS_VALIDATED,
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function examinationType()
    {
        return $this->belongsTo(ExaminationType::class);
    }

    public function referringDoctor()
    {
        return $this->belongsTo(Doctor::class, 'referring_doctor_id');
    }

    public function radiographer()
    {
        return $this->belongsTo(Radiographer::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function report()
    {
        return $this->hasOne(RadiologyReport::class, 'order_id');
    }

    public function result()
    {
        return $this->hasOne(RadiologyResult::class, 'radiology_order_id');
    }

    public function studyMetadata()
    {
        return $this->hasOne(StudyMetadata::class, 'order_id');
    }

    public function worklistLogs()
    {
        return $this->hasMany(WorklistLog::class, 'order_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ORDERED => '<span class="badge bg-secondary">Ordered</span>',
            self::STATUS_WAITING_SAMPLE => '<span class="badge bg-warning text-dark"><i data-feather="clock" style="width:12px;height:12px"></i> Menunggu Sample</span>',
            self::STATUS_SAMPLE_TAKEN => '<span class="badge bg-info text-dark"><i data-feather="check-circle" style="width:12px;height:12px"></i> Sample Diambil</span>',
            self::STATUS_IN_PROGRESS => '<span class="badge bg-primary"><i data-feather="activity" style="width:12px;height:12px"></i> Sedang Periksa</span>',
            self::STATUS_COMPLETED => '<span class="badge" style="background-color:#198754"><i data-feather="check" style="width:12px;height:12px"></i> Selesai Periksa</span>',
            self::STATUS_REPORTED => '<span class="badge" style="background-color:#0f9d58"><i data-feather="file-text" style="width:12px;height:12px"></i> Hasil Diinput</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-danger">Batal</span>',
            self::STATUS_SENT_TO_PACS => '<span class="badge bg-info text-white"><i data-feather="send" style="width:12px;height:12px"></i> Kirim PACS</span>',
            self::STATUS_VALIDATED => '<span class="badge bg-success"><i data-feather="check-square" style="width:12px;height:12px"></i> Validasi</span>',
            default => '<span class="badge bg-dark">' . $this->status . '</span>',
        };
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->scheduled_date ? \Carbon\Carbon::parse($this->scheduled_date)->format('d/m/Y') : '-';
    }

    public function getDicomDateAttribute(): string
    {
        return $this->scheduled_date ? \Carbon\Carbon::parse($this->scheduled_date)->format('Ymd') : '';
    }

    public function getDicomTimeAttribute(): string
    {
        return $this->scheduled_time ? str_replace(':', '', $this->scheduled_time) : '';
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function getDurationReqSampleAttribute(): ?int
    {
        if (!$this->waktu_sample)
            return null;
        return \Carbon\Carbon::parse($this->created_at)->diffInMinutes(\Carbon\Carbon::parse($this->waktu_sample));
    }

    public function getDurationSampleResultAttribute(): ?int
    {
        if (!$this->waktu_sample || !$this->result || !$this->result->waktu_hasil)
            return null;
        return \Carbon\Carbon::parse($this->waktu_sample)->diffInMinutes(\Carbon\Carbon::parse($this->result->waktu_hasil));
    }

    public function getDurationTotalAttribute(): ?int
    {
        if (!$this->result || !$this->result->waktu_hasil)
            return null;
        return \Carbon\Carbon::parse($this->created_at)->diffInMinutes(\Carbon\Carbon::parse($this->result->waktu_hasil));
    }

    public function formatDuration(?int $minutes): string
    {
        if ($minutes === null)
            return '-';
        if ($minutes < 60)
            return $minutes . 'm';
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        return $h . 'j ' . $m . 'm';
    }
}
