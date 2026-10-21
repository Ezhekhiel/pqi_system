<?php

namespace App\Services;

class FirebaseService
{
    private array $serviceAccount;

    private string $projectId;

    public function __construct()
    {
        $path = storage_path('app/firebase/fcm.json');

        if (! file_exists($path)) {
            throw new \Exception('FCM service account not found');
        }

        $this->serviceAccount = json_decode(file_get_contents($path), true);
        $this->projectId = $this->serviceAccount['project_id'];
    }

    private function base64UrlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getAccessToken(): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $now = time();
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = "$header.$claim";

        openssl_sign(
            $signatureInput,
            $signature,
            $this->serviceAccount['private_key'],
            OPENSSL_ALGO_SHA256
        );

        $jwt = "$signatureInput.".$this->base64UrlEncode($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);

        return $json['access_token'];
    }

    public function send(array $payload)
    {
        $token = $this->getAccessToken();

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token",
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
