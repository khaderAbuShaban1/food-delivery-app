<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class ApiService
{
    protected string $baseUrl;
    protected ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = config('app.api_url', 'http://127.0.0.1:8000/api');
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
        Session::put('api_token', $token);
    }

    public function getToken(): ?string
    {
        if (!$this->token) {
            $this->token = Session::get('api_token');
        }
        return $this->token;
    }

    public function clearToken(): void
    {
        $this->token = null;
        Session::forget('api_token');
    }

    public function get(string $endpoint, array $data = []): ?array
    {
        return $this->request('GET', $endpoint, $data);
    }

    public function post(string $endpoint, array $data = []): ?array
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function postWithFile(string $endpoint, array $data = [], ?UploadedFile $file = null): ?array
    {
        $url = $this->baseUrl . $endpoint;
        $token = $this->getToken();

        try {
            $multipart = [];
            foreach ($data as $key => $value) {
                $multipart[] = ['name' => $key, 'contents' => $value];
            }
            if ($file) {
                $multipart[] = [
                    'name' => 'image',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                ];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])
            ->withToken($token)
            ->asMultipart()
            ->post($url, $multipart);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Request failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function put(string $endpoint, array $data = []): ?array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    public function putWithFile(string $endpoint, array $data = [], ?UploadedFile $file = null): ?array
    {
        $url = $this->baseUrl . $endpoint;
        $token = $this->getToken();

        try {
            $multipart = [];
            foreach ($data as $key => $value) {
                $multipart[] = ['name' => $key, 'contents' => $value];
            }
            if ($file) {
                $multipart[] = [
                    'name' => 'image',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                ];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])
            ->withToken($token)
            ->asMultipart()
            ->post($url, array_merge($multipart, [['name' => '_method', 'contents' => 'PUT']]));

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Request failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function delete(string $endpoint): ?array
    {
        return $this->request('DELETE', $endpoint);
    }

    protected function request(string $method, string $endpoint, array $data = []): ?array
    {
        $url = $this->baseUrl . $endpoint;
        $token = $this->getToken();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->withToken($token)
            ->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Request failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}