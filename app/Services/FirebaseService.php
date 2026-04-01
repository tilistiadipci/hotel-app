<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected function getAccessToken()
    {
        $path = storage_path(config('services.firebase.credentials'));
        $credentials = json_decode(file_get_contents($path), true);

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $now = time();

        $payload = [
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $base64UrlEncode = function ($data) {
            return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
        };

        $jwtHeader = $base64UrlEncode($header);
        $jwtPayload = $base64UrlEncode($payload);

        $signature = '';
        openssl_sign(
            $jwtHeader . "." . $jwtPayload,
            $signature,
            $credentials['private_key'],
            'SHA256'
        );

        $jwtSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $jwt = $jwtHeader . "." . $jwtPayload . "." . $jwtSignature;

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $response->json()['access_token'] ?? null;
    }

    public function sendToToken($token, $data)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception("Failed to get Firebase access token");
        }

        $projectId = config('services.firebase.project_id');

        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'data' => $data,
                ]
            ]);

        return $response->json();
    }

    public function sendToTopic($topic, $data)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception("Failed to get Firebase access token");
        }

        $projectId = config('services.firebase.project_id');

        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'topic' => $topic,
                    'data' => $data,
                ]
            ]);

        return $response->json();
    }
}
