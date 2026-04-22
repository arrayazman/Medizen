<?php

namespace App\Http\Controllers;

use App\Services\OrthancService;
use Illuminate\Http\Request;

class OrthancController extends Controller
{
    protected OrthancService $orthanc;

    public function __construct(OrthancService $orthanc)
    {
        $this->orthanc = $orthanc;
    }

    public function forward(Request $request)
    {
        $path = ltrim($request->path(), '/');

        $options = [];
        if (!empty($request->query())) {
            $options['query'] = $request->query();
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $options['body'] = $request->getContent();
        }

        $response = $this->orthanc->forward($request->method(), $path, $options);

        // Abaikan header yang bisa memicu popup login browser atau konflik encoding
        $headers = [];
        foreach ($response->headers() as $name => $values) {
            $lowerName = strtolower($name);
            if (!in_array($lowerName, ['transfer-encoding', 'connection', 'content-length', 'www-authenticate'])) {
                $headers[$name] = implode(', ', $values);
            }
        }

        // Tambahkan CORS agar viewer lancar
        $headers['Access-Control-Allow-Origin'] = '*';

        if ($response->status() !== 200) {
            \Log::warning("Orthanc Proxy: /{$path} returned status {$response->status()}");
        }

        return response($response->body(), $response->status())
            ->withHeaders($headers);
    }
}
