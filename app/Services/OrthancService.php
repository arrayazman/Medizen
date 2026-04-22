<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrthancService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.orthanc.url'), '/');
        $this->username = config('services.orthanc.username');
        $this->password = config('services.orthanc.password');
    }

    protected function client()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->withOptions([
                'timeout' => 0,
                'connect_timeout' => 10,
            ]);
    }

    public function forward(string $method, string $path, array $options = [])
    {
        try {
            return $this->client()->send($method, $this->baseUrl . '/' . $path, $options);
        } catch (\Exception $e) {
            Log::error("Orthanc Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function saveModality(string $id, array $data)
    {
        try {
            // Ensure we send as JSON explicitly
            $response = $this->client()->asJson()->put($this->baseUrl . '/modalities/' . $id, $data);
            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error("Orthanc saveModality Error: " . $e->getMessage());
            return ['success' => false, 'body' => $e->getMessage()];
        }
    }
}
