<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PACSClient
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('pacs.url'), '/');
        $this->username = config('pacs.username');
        $this->password = config('pacs.password');
        $this->timeout = config('pacs.timeout', 30);
    }

    // ============================
    // HTTP Primitives
    // ============================

    public function get(string $endpoint, array $params = [])
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}{$endpoint}", $params);
            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('PACS GET error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function getRaw(string $endpoint): ?string
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}{$endpoint}");
            return $response->successful() ? $response->body() : null;
        } catch (\Exception $e) {
            Log::error('PACS GET raw error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function post(string $endpoint, $data = [], int $customTimeout = null): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($customTimeout ?? $this->timeout)
                ->post("{$this->baseUrl}{$endpoint}", $data);
            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'body' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('PACS POST error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['success' => false, 'status' => 0, 'data' => null, 'body' => $e->getMessage()];
        }
    }

    public function put(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->put("{$this->baseUrl}{$endpoint}", $data);
            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'body' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('PACS PUT error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['success' => false, 'status' => 0, 'data' => null, 'body' => $e->getMessage()];
        }
    }

    public function delete(string $endpoint): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->delete("{$this->baseUrl}{$endpoint}");
            return ['success' => $response->successful(), 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error('PACS DELETE error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['success' => false, 'status' => 0];
        }
    }

    // ============================
    // System & Statistics
    // ============================

    public function isAvailable(): bool
    {
        return $this->get('/system') !== null;
    }
    public function getSystem(): ?array
    {
        return $this->get('/system');
    }
    public function getStatistics(): ?array
    {
        return $this->get('/statistics');
    }
    public function getPlugins(): ?array
    {
        return $this->get('/plugins');
    }

    // ============================
    // Patients
    // ============================

    public function getPatients(): ?array
    {
        return $this->get('/patients');
    }
    public function getPatient(string $id): ?array
    {
        return $this->get("/patients/{$id}");
    }
    public function getPatientStudies(string $id): ?array
    {
        return $this->get("/patients/{$id}/studies");
    }
    public function deletePatient(string $id): array
    {
        return $this->delete("/patients/{$id}");
    }

    /**
     * Get patients paginated using PACS native since/limit
     */
    public function getPatientsPaginated(int $since = 0, int $limit = 50): ?array
    {
        return $this->get('/patients', ['since' => $since, 'limit' => $limit, 'expand' => true]);
    }

    // ============================
    // Studies
    // ============================

    public function getStudies(): ?array
    {
        return $this->get('/studies');
    }
    public function getStudy(string $id): ?array
    {
        return $this->get("/studies/{$id}");
    }
    public function getStudyStatistics(string $id): ?array
    {
        return $this->get("/studies/{$id}/statistics");
    }
    public function deleteStudy(string $id): array
    {
        return $this->delete("/studies/{$id}");
    }

    /**
     * Get studies paginated using PACS native since/limit
     */
    public function getStudiesPaginated(int $since = 0, int $limit = 50): ?array
    {
        return $this->get('/studies', ['since' => $since, 'limit' => $limit, 'expand' => true]);
    }

    /**
     * Modify DICOM tags of a study (creates new modified copy)
     */
    public function modifyStudy(string $id, array $replace, bool $keepSource = false, array $keep = []): array
    {
        $payload = [
            'Replace' => $replace,
            'KeepSource' => $keepSource,
            'Force' => true,
        ];

        if (!empty($keep)) {
            $payload['Keep'] = $keep;
        }

        return $this->post("/studies/{$id}/modify", $payload);
    }

    // ============================
    // Series
    // ============================

    public function getSeries(string $id): ?array
    {
        return $this->get("/series/{$id}");
    }
    public function getSeriesInstances(string $id): ?array
    {
        return $this->get("/series/{$id}/instances");
    }
    public function deleteSeries(string $id): array
    {
        return $this->delete("/series/{$id}");
    }

    // ============================
    // Instances
    // ============================

    public function getInstance(string $id): ?array
    {
        return $this->get("/instances/{$id}");
    }
    public function getInstanceTags(string $id): ?array
    {
        return $this->get("/instances/{$id}/simplified-tags");
    }
    public function getInstanceHeader(string $id): ?array
    {
        return $this->get("/instances/{$id}/header");
    }
    public function deleteInstance(string $id): array
    {
        return $this->delete("/instances/{$id}");
    }

    // ============================
    // Search / Find
    // ============================

    public function findStudies(array $query, int $limit = 100, int $since = 0): ?array
    {
        $payload = ['Level' => 'Study', 'Query' => $query, 'Expand' => true];
        if ($limit > 0) {
            $payload['Limit'] = $limit;
        }
        if ($since > 0) {
            $payload['Since'] = $since;
        }
        $result = $this->post('/tools/find', $payload);
        return $result['success'] ? $result['data'] : null;
    }

    public function findPatients(array $query, int $limit = 100, int $since = 0): ?array
    {
        $payload = ['Level' => 'Patient', 'Query' => $query, 'Expand' => true];
        if ($limit > 0) {
            $payload['Limit'] = $limit;
        }
        if ($since > 0) {
            $payload['Since'] = $since;
        }
        $result = $this->post('/tools/find', $payload);
        return $result['success'] ? $result['data'] : null;
    }

    // ============================
    // DICOM Modalities (peers)
    // ============================

    public function getModalities(): ?array
    {
        return $this->get('/modalities');
    }
    public function getModalityDetails(string $name): ?array
    {
        return $this->get("/modalities/{$name}");
    }

    public function getModalityConfiguration(string $name): ?array
    {
        // Try getting specific configuration sub-endpoint if it exist, otherwise fallback to main
        $config = $this->get("/modalities/{$name}/configuration");
        if (!$config) {
            $config = $this->get("/modalities/{$name}");
        }
        return $config;
    }

    public function upsertModality(string $name, array $data): array
    {
        return $this->put("/modalities/{$name}", $data);
    }

    public function deleteModalityDevice(string $name): array
    {
        return $this->delete("/modalities/{$name}");
    }

    // ============================
    // Worklists
    // ============================

    public function getWorklists(): ?array
    {
        return $this->get('/worklists');
    }

    public function getWorklistFile(string $filename): ?string
    {
        return $this->getRaw("/worklists/{$filename}");
    }

    // ============================
    // URL Builders & Raw Bytes
    // ============================

    public function getInstancePreview(string $id): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/instances/{$id}/preview");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $result !== false) {
            return $result;
        }

        return null;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    // Public URL (via Nginx proxy) - used for viewer links in browser (no auth prompt)
    public function getPublicUrl(): string
    {
        $public = config('pacs.public_url');

        // Jika public_url sama dengan internal baseUrl, berarti tidak ada reverse proxy eksternal.
        // Gunakan URL aplikasi saat ini agar melewati Laravel Proxy (menghindari prompt login).
        if ($public === $this->baseUrl) {
            return url('/');
        }

        return rtrim($public ?? url('/'), '/');
    }

    public function getPreviewUrl(string $instanceId): string
    {
        return "{$this->baseUrl}/instances/{$instanceId}/preview";
    }

    public function getInstanceImageUrl(string $instanceId): string
    {
        return "{$this->baseUrl}/instances/{$instanceId}/preview";
    }

    // Viewer URLs use publicUrl (Nginx proxy port) so no browser auth prompt
    public function getOHIFViewerUrl(string $studyInstanceUID): string
    {
        return url("/ohif/viewer?StudyInstanceUIDs={$studyInstanceUID}");
    }

    public function getOsimisViewerUrl(string $studyId): string
    {
        return "{$this->getPublicUrl()}/osimis-viewer/app/index.html?study={$studyId}";
    }

    public function getStoneViewerUrl(string $studyId): string
    {
        return "{$this->getPublicUrl()}/stone-webviewer/index.html?study={$studyId}";
    }

    public function getExplorerUrl(string $studyId): string
    {
        return "{$this->getPublicUrl()}/app/explorer.html#study?uuid={$studyId}";
    }

    public function getStudyArchiveUrl(string $studyId): string
    {
        return "{$this->baseUrl}/studies/{$studyId}/archive";
    }

    public function getSeriesArchiveUrl(string $seriesId): string
    {
        return "{$this->baseUrl}/series/{$seriesId}/archive";
    }

    public function getStudyMediaUrl(string $studyId): string
    {
        return "{$this->baseUrl}/studies/{$studyId}/media";
    }
}


