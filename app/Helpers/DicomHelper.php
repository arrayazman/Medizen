<?php

namespace App\Helpers;

class DicomHelper
{
    /**
     * Generate a unique Accession Number.
     * Format: Numeric, exactly 10 digits (ymd + 4 random digits)
     */
    public static function generateAccessionNumber(): string
    {
        $date = now()->format('ymd'); // 6 digits (YYMMDD)
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT); // 4 digits
        return "{$date}{$random}";
    }

    /**
     * Generate a valid DICOM StudyInstanceUID.
     * Format: 1.2.826.0.1.3680043.8.498.TIMESTAMP.RANDOM
     */
    public static function generateStudyInstanceUID(): string
    {
        $root = '1.2.826.0.1.3680043.8.498';
        $timestamp = now()->format('YmdHis');
        $random = mt_rand(100000, 999999);
        return "{$root}.{$timestamp}.{$random}";
    }

    /**
     * Generate Order Number.
     * Format: ORD-YYYYMMDD-XXXX
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        return "ORD-{$date}-{$random}";
    }

    /**
     * Format date to DICOM format (YYYYMMDD).
     */
    public static function formatDicomDate($date): string
    {
        return \Carbon\Carbon::parse($date)->format('Ymd');
    }

    /**
     * Format time to DICOM format (HHMMSS).
     */
    public static function formatDicomTime($time): string
    {
        return str_replace(':', '', $time);
    }
}
