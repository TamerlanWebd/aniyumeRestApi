<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FirestoreRestService
{
    protected $projectId;
    protected $credentials;

    public function __construct()
    {
        $credentialsPath = storage_path('app/firebase/firebase-credentials.json');
        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException("Firebase credentials not found at: {$credentialsPath}");
        }
        
        $this->credentials = json_decode(file_get_contents($credentialsPath), true);
        $this->projectId = $this->credentials['project_id'];
    }

    protected function getAccessToken()
    {
        return Cache::remember('firebase_access_token', 3300, function () {
            $scopes = ['https://www.googleapis.com/auth/datastore'];
            $auth = new ServiceAccountCredentials($scopes, $this->credentials);
            return $auth->fetchAuthToken()['access_token'];
        });
    }

    protected function baseUrl()
    {
        return "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

    public function collection(string $name)
    {
        return new FirestoreCollection($this, $name);
    }

    public function get(string $path, array $params = [])
    {
        $url = $this->baseUrl() . '/' . $path;
        $response = Http::withToken($this->getAccessToken())->get($url, $params);
        return $response;
    }

    public function post(string $path, array $body)
    {
        $url = $this->baseUrl() . '/' . $path;
        $response = Http::withToken($this->getAccessToken())->post($url, $body);
        return $response;
    }

    public function patch(string $path, array $body, array $params = [])
    {
        $url = $this->baseUrl() . '/' . $path;
        $response = Http::withToken($this->getAccessToken())->patch($url, $body, $params);
        return $response;
    }

    public function delete(string $path)
    {
        $url = $this->baseUrl() . '/' . $path;
        $response = Http::withToken($this->getAccessToken())->delete($url);
        return $response;
    }
}

class FirestoreCollection
{
    protected $service;
    protected $name;

    public function __construct(FirestoreRestService $service, string $name)
    {
        $this->service = $service;
        $this->name = $name;
    }

    public function list(int $limit = 10, int $offset = 0)
    {
        // Firestore REST API uses pageToken, not offset. 
        // For simple offset simulation we would need to fetch and skip, which is expensive.
        // However, for this implementation we will just use pageSize. 
        // Proper offset pagination in Firestore requires cursors.
        // We will stick to simple pageSize for now as requested.
        
        $response = $this->service->get($this->name, [
            'pageSize' => $limit,
            // 'pageToken' => ... // TODO: Implement cursor based pagination if needed
        ]);

        if ($response->failed()) {
            throw new \Exception("Firestore Error: " . $response->body());
        }

        $documents = $response->json()['documents'] ?? [];
        return array_map(fn($doc) => $this->formatDoc($doc), $documents);
    }

    public function get(string $id)
    {
        $response = $this->service->get("{$this->name}/{$id}");
        
        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new \Exception("Firestore Error: " . $response->body());
        }

        return $this->formatDoc($response->json());
    }

    public function add(array $data)
    {
        $fields = $this->encodeFields($data);
        $response = $this->service->post($this->name, ['fields' => $fields]);

        if ($response->failed()) {
            throw new \Exception("Firestore Error: " . $response->body());
        }

        return $this->formatDoc($response->json());
    }

    public function update(string $id, array $data)
    {
        $fields = $this->encodeFields($data);
        $fieldPaths = array_keys($fields);
        
        $queryParams = [
            'updateMask.fieldPaths' => $fieldPaths
        ];

        $response = $this->service->patch("{$this->name}/{$id}", ['fields' => $fields], $queryParams);

        if ($response->status() === 404) {
            return false;
        }

        if ($response->failed()) {
            throw new \Exception("Firestore Error: " . $response->body());
        }

        return $this->formatDoc($response->json());
    }

    public function delete(string $id)
    {
        $response = $this->service->delete("{$this->name}/{$id}");
        return $response->successful();
    }

    protected function formatDoc(array $doc)
    {
        $id = basename($doc['name']);
        $data = [];
        
        if (isset($doc['fields'])) {
            foreach ($doc['fields'] as $key => $value) {
                // Simple type mapping
                $type = array_key_first($value);
                $data[$key] = $value[$type];
            }
        }

        return array_merge(['id' => $id], $data);
    }

    protected function encodeFields(array $data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            // Simple type inference
            if (is_string($value)) {
                $fields[$key] = ['stringValue' => $value];
            } elseif (is_int($value)) {
                $fields[$key] = ['integerValue' => $value];
            } elseif (is_bool($value)) {
                $fields[$key] = ['booleanValue' => $value];
            } elseif (is_double($value)) {
                $fields[$key] = ['doubleValue' => $value];
            }
            // Add more types as needed
        }
        return $fields;
    }
}
