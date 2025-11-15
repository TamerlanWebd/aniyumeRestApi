<?php
namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class AnimeController extends Controller
{
    private $accessToken;
    private $projectId;

    public function __construct()
    {
        $credentials = json_decode(file_get_contents(storage_path('app/firebase/firebase-credentials.json')), true);
        $this->projectId = $credentials['project_id'];
        $scopes = ['https://www.googleapis.com/auth/datastore'];
        $auth = new \Google\Auth\Credentials\ServiceAccountCredentials($scopes, $credentials);
        $this->accessToken = $auth->fetchAuthToken()['access_token'];
    }

    public function index()
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/anime";
        $response = Http::withToken($this->accessToken)->get($url);
        $documents = $response->json()['documents'] ?? [];
        $animeList = [];
        foreach ($documents as $doc) {
            $fields = $doc['fields'] ?? [];
            $data = [];
            foreach ($fields as $key => $value) {
                $type = array_key_first($value);
                $data[$key] = $value[$type];
            }
            $id = basename($doc['name']);
            $animeList[] = array_merge(['id' => $id], $data);
        }
        return response()->json($animeList);
    }

    public function show($id)
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/anime/{$id}";
        $response = Http::withToken($this->accessToken)->get($url);
        if ($response->status() === 404) return response()->json(['error' => 'Anime not found'], 404);
        $doc = $response->json();
        $fields = $doc['fields'] ?? [];
        $data = [];
        foreach ($fields as $key => $value) {
            $type = array_key_first($value);
            $data[$key] = $value[$type];
        }
        return response()->json(array_merge(['id' => $id], $data));
    }

    public function store(Request $request)
    {
        $fields = [
            'title' => ['stringValue' => $request->input('title')],
            'genre' => ['stringValue' => $request->input('genre')],
            'description' => ['stringValue' => $request->input('description')],
            'imageUrl' => ['stringValue' => $request->input('imageUrl')],
        ];
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/anime";
        $response = Http::withToken($this->accessToken)->post($url, ['fields' => $fields]);
        $doc = $response->json();
        $id = basename($doc['name']);
        return response()->json(['id' => $id] + $request->all(), 201);
    }

    public function update(Request $request, $id)
    {
        $fields = [];
        foreach (['title', 'genre', 'description', 'imageUrl'] as $field) {
            if ($request->has($field)) {
                $fields[$field] = ['stringValue' => $request->input($field)];
            }
        }
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/anime/{$id}?updateMask.fieldPaths=" . implode('&updateMask.fieldPaths=', array_keys($fields));
        $response = Http::withToken($this->accessToken)->patch($url, ['fields' => $fields]);
        $doc = $response->json();
        return response()->json(['id' => $id] + $request->all());
    }

    public function destroy($id)
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/anime/{$id}";
        Http::withToken($this->accessToken)->delete($url);
        return response()->json(['message' => 'deleted']);
    }
}
