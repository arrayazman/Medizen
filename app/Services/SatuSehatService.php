<?php

namespace App\Services;

use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SatuSehatService
{
    protected InstitutionSetting $setting;
    protected string $baseUrl;
    protected string $authUrl;

    public function __construct()
    {
        // Re-fetch to get latest from DB in case of singleton usage
        $this->setting = InstitutionSetting::first() ?? new InstitutionSetting();
        $env = $this->setting->satusehat_env ?? 'sandbox';

        if ($env === 'production') {
            $this->baseUrl = 'https://api-satusehat.kemkes.go.id/fhir-r4/v1';
            $this->authUrl = 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        } else {
            // STAGING / SANDBOX Environment
            $this->baseUrl = 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1';
            $this->authUrl = 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        }
    }

    // ========================
    // AUTH
    // ========================

    public function getAccessToken(): ?string
    {
        $env = $this->setting->satusehat_env ?? 'sandbox';
        $cacheKey = 'satusehat_token_' . $env;

        $token = Cache::get($cacheKey);
        if ($token) {
            return $token;
        }

        $clientId = $this->setting->satusehat_client_id;
        $clientSecret = $this->setting->satusehat_client_secret;

        if (!$clientId || !$clientSecret) {
            Log::warning('SatuSehat: client_id atau client_secret belum dikonfigurasi.', [
                'env' => $env,
                'org_id' => $this->setting->satusehat_organization_id
            ]);
            return null;
        }

        try {
            Log::info("SatuSehat: Mencoba login ke $env...", ['url' => $this->authUrl]);

            $response = Http::asForm()
                ->withoutVerifying() // FIX cURL error 77: Missing cert file
                ->timeout(15)
                ->post($this->authUrl, [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                $expiresIn = $data['expires_in'] ?? 3600;

                if ($token) {
                    // Cache token 10 menit lebih cepat dari expired asli (safety margin)
                    Cache::put($cacheKey, $token, max(60, $expiresIn - 600));
                    Log::info('SatuSehat: Token berhasil didapatkan.');
                    return $token;
                }
            }

            Log::error('SatuSehat: Gagal login (HTTP ' . $response->status() . ')', [
                'url' => $this->authUrl,
                'env' => $env,
                'response' => $response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('SatuSehat: Exception saat login', [
                'error' => $e->getMessage(),
                'env' => $env,
                'hint' => 'Check your php.ini curl.cainfo setting, or make sure Laragon cert exists.'
            ]);
            return null;
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->setting->satusehat_organization_id)
            && !empty($this->setting->satusehat_client_id)
            && !empty($this->setting->satusehat_client_secret);
    }

    public function getOrganizationId(): ?string
    {
        return $this->setting->satusehat_organization_id ?: null;
    }

    // ========================
    // FHIR HTTP HELPERS
    // ========================

    protected function request(string $method, string $path, array $body = []): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Tidak dapat mendapatkan access token SatuSehat.'];
        }

        try {
            $http = Http::withToken($token)
                ->withoutVerifying() // FIX cURL error 77: Missing cert file
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30);

            $url = $this->baseUrl . '/' . ltrim($path, '/');

            $response = match (strtoupper($method)) {
                'POST' => $http->post($url, $body),
                'PUT' => $http->put($url, $body),
                'GET' => $http->get($url),
                default => $http->get($url),
            };

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'body' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('SatuSehat FHIR request error', ['path' => $path, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ========================
    // PATIENT
    // ========================

    /**
     * Cari pasien di SatuSehat berdasarkan NIK
     */
    public function findPatientByNik(string $nik): array
    {
        return $this->request('GET', "Patient?identifier=https://fhir.kemkes.go.id/id/nik|{$nik}");
    }

    // ========================
    // SERVICE REQUEST (Radiology Order)
    // ========================

    /**
     * Kirim ServiceRequest (permintaan pemeriksaan radiologi) ke SatuSehat
     */
    public function sendServiceRequest(\App\Models\RadiologyOrder $order): array
    {
        $mapping = $order->examinationType?->satusehatMapping;
        $orgId = $this->getOrganizationId();

        // Build FHIR ServiceRequest resource
        $resource = [
            'resourceType' => 'ServiceRequest',
            'status' => 'active',
            'intent' => 'order',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'code' => '363679005',
                            'display' => 'Imaging',
                        ]
                    ]
                ]
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => $mapping?->system ?? 'http://loinc.org',
                        'code' => $mapping?->code ?? $mapping?->examination_code ?? $order->examinationType?->code ?? '',
                        'display' => $mapping?->display ?? $order->examinationType?->name ?? '',
                    ]
                ],
                'text' => $order->examinationType?->name ?? 'Pemeriksaan Radiologi',
            ],
            'subject' => [
                'reference' => 'Patient/' . ($order->patient?->satusehat_id ?? 'unknown'),
                'display' => $order->patient?->nama ?? '',
            ],
            'requester' => [
                'reference' => 'Organization/' . $orgId,
                'display' => InstitutionSetting::first()?->hospital_name ?? 'Fasyankes',
            ],
            'performer' => [
                [
                    'reference' => 'Organization/' . $orgId,
                ]
            ],
            'authoredOn' => optional($order->created_at)->toAtomString() ?? now()->toAtomString(),
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/servicerequest/' . $orgId,
                    'value' => $order->accession_number ?? 'ORDER-' . $order->id,
                ]
            ],
        ];

        if ($order->clinical_info) {
            $resource['note'] = [['text' => $order->clinical_info]];
        }

        $result = $this->request('POST', 'ServiceRequest', $resource);

        if ($result['success']) {
            // Simpan ID yang dikembalikan SatuSehat
            $fhirId = $result['data']['id'] ?? null;
            if ($fhirId) {
                $order->update(['satusehat_service_request_id' => $fhirId]);
            }
        } else {
            Log::error('SatuSehat: Gagal kirim ServiceRequest', [
                'order_id' => $order->id,
                'response' => $result['body'] ?? $result['error'] ?? ''
            ]);
        }

        return $result;
    }

    // ========================
    // IMAGING STUDY (Hasil Pemeriksaan)
    // ========================

    /**
     * Kirim ImagingStudy (hasil radiologi) ke SatuSehat
     */
    public function sendImagingStudy(\App\Models\RadiologyOrder $order): array
    {
        $mapping = $order->examinationType?->satusehatMapping;
        $orgId = $this->getOrganizationId();
        $result_obj = $order->result;
        $modality = $order->modality ?? $order->examinationType?->modality?->name ?? 'OT';

        $resource = [
            'resourceType' => 'ImagingStudy',
            'status' => 'available',
            'identifier' => [
                [
                    'system' => 'urn:dicom:uid',
                    'value' => 'urn:oid:' . ($order->study_instance_uid ?? '2.25.' . mt_rand()),
                ],
                [
                    'system' => 'http://sys-ids.kemkes.go.id/imagingstudy/' . $orgId,
                    'value' => $order->accession_number ?? 'STUDY-' . $order->id,
                ],
            ],
            'modality' => [
                [
                    'system' => 'http://dicom.nema.org/resources/ontology/DCM',
                    'code' => strtoupper($modality),
                    'display' => strtoupper($modality),
                ]
            ],
            'subject' => [
                'reference' => 'Patient/' . ($order->patient?->satusehat_id ?? 'unknown'),
                'display' => $order->patient?->nama ?? '',
            ],
            'encounter' => $order->satusehat_encounter_id
                ? ['reference' => 'Encounter/' . $order->satusehat_encounter_id]
                : null,
            'started' => optional($order->waktu_mulai_periksa ?? $order->created_at)->toAtomString(),
            'numberOfSeries' => 1,
            'numberOfInstances' => 1,
            'procedureCode' => [
                [
                    'coding' => [
                        [
                            'system' => $mapping?->system ?? 'http://loinc.org',
                            'code' => $mapping?->code ?? $order->examinationType?->code ?? '',
                            'display' => $mapping?->display ?? $order->examinationType?->name ?? '',
                        ]
                    ]
                ]
            ],
            'basedOn' => $order->satusehat_service_request_id
                ? [['reference' => 'ServiceRequest/' . $order->satusehat_service_request_id]]
                : [],
            'location' => [
                'reference' => 'Organization/' . $orgId,
            ],
            'reasonCode' => $order->clinical_info
                ? [['text' => $order->clinical_info]]
                : [],
            'note' => $result_obj?->expertise
                ? [['text' => $result_obj->expertise]]
                : [],
            'series' => [
                [
                    'uid' => '2.25.' . mt_rand(),
                    'number' => 1,
                    'modality' => [
                        'system' => 'http://dicom.nema.org/resources/ontology/DCM',
                        'code' => strtoupper($modality),
                    ],
                    'description' => $order->examinationType?->name ?? 'Series',
                    'numberOfInstances' => 1,
                    'instance' => [
                        [
                            'uid' => '2.25.' . mt_rand(),
                            'sopClass' => [
                                'system' => 'urn:ietf:rfc:3986',
                                'code' => 'urn:oid:1.2.840.10008.5.1.4.1.1.7',
                            ],
                            'number' => 1,
                        ]
                    ],
                ]
            ],
        ];

        // Remove null keys
        $resource = array_filter($resource, fn($v) => !is_null($v));

        $result = $this->request('POST', 'ImagingStudy', $resource);

        if ($result['success']) {
            $fhirId = $result['data']['id'] ?? null;
            if ($fhirId) {
                $order->update(['satusehat_imaging_study_id' => $fhirId]);
            }
        } else {
            Log::error('SatuSehat: Gagal kirim ImagingStudy', [
                'order_id' => $order->id,
                'response' => $result['body'] ?? $result['error'] ?? ''
            ]);
        }

        return $result;
    }

    // ========================
    // SEND ALL (One-click)
    // ========================

    /**
     * Kirim ServiceRequest + ImagingStudy sekaligus untuk satu order
     */
    public function sendOrder(\App\Models\RadiologyOrder $order): array
    {
        $results = [];

        // 1. Kirim ServiceRequest jika belum
        if (empty($order->satusehat_service_request_id)) {
            $results['service_request'] = $this->sendServiceRequest($order);
            $order->refresh();
        }

        // 2. Kirim ImagingStudy jika hasil sudah FINAL
        if ($order->result?->status === 'FINAL' && empty($order->satusehat_imaging_study_id)) {
            $results['imaging_study'] = $this->sendImagingStudy($order);
        }

        return $results;
    }
}
