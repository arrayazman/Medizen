<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SatuSehatRadiologiService
{
    private $authUrl;
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    private $orgId;
    private $token;

    public function __construct()
    {
        $this->authUrl = config('satusehat.auth_url');
        $this->baseUrl = config('satusehat.base_url');
        $this->clientId = config('satusehat.client_id');
        $this->clientSecret = config('satusehat.client_secret');
        $this->orgId = config('satusehat.organization_id');
    }

    /**
     * Mendapatkan akses token dari OAuth2 SATUSEHAT
     * Setara dengan TokenSatuSehat() pada Java
     *
     * @return string
     * @throws \Exception
     */
    public function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $response = Http::asForm()->withoutVerifying()->post($this->authUrl . '/accesstoken?grant_type=client_credentials', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to retrieve Token: ' . $response->body());
        }

        $this->token = $response->json('access_token', '');
        return $this->token;
    }

    /**
     * Mengambil ID Pasien (Patient) FHIR berdasarkan NIK
     *
     * @param string $nik
     * @return string
     */
    public function getPatientId(string $nik): string
    {
        if (empty($nik)) return '';
        
        $token = $this->getToken();
        $url = rtrim($this->baseUrl, '/') . '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;
        
        $response = Http::withToken($token)->withoutVerifying()->get($url);
        
        $id = $response->json('entry.0.resource.id', '');
        if (!$id && !$response->successful()) {
            throw new \Exception("Gagal Query API Pasien ({$nik}): " . $response->body());
        }
        return $id;
    }

    /**
     * Mengambil ID Dokter (Practitioner) FHIR berdasarkan NIK
     *
     * @param string $nik
     * @return string
     */
    public function getPractitionerId(string $nik): string
    {
        if (empty($nik)) return '';
        
        $token = $this->getToken();
        $url = rtrim($this->baseUrl, '/') . '/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;
        
        $response = Http::withToken($token)->withoutVerifying()->get($url);
        
        return $response->json('entry.0.resource.id', '');
    }

    /**
     * POST / PUT Resource FHIR ke server SATUSEHAT
     * 
     * @param string $resourceType
     * @param array $payload
     * @param string|null $id (jika PUT)
     * @return array
     */
    public function sendResource(string $resourceType, array $payload, ?string $id = null): array
    {
        $token = $this->getToken();
        $url = rtrim($this->baseUrl, '/') . '/' . $resourceType;

        if ($id) {
            $url .= '/' . $id;
            $response = Http::withToken($token)->withoutVerifying()->withHeaders([
                'Content-Type' => 'application/fhir+json'
            ])->put($url, $payload);
        } else {
            $response = Http::withToken($token)->withoutVerifying()->withHeaders([
                'Content-Type' => 'application/fhir+json'
            ])->post($url, $payload);
        }

        if (!$response->successful()) {
            throw new \Exception('API Error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Mengembalikan Organization ID SATUSEHAT yang di set di ENV
     *
     * @return string
     */
    public function getOrganizationId(): string
    {
        return $this->orgId;
    }
}
