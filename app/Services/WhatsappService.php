<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('WA_GATEWAY_URL', 'http://127.0.0.1:3001');
        $this->apiKey  = env('WA_GATEWAY_KEY', 'my-secret-key');
    }

    private function client()
    {
        return Http::withHeaders([
            'x-api-key' => $this->apiKey,
        ])->baseUrl($this->baseUrl)->timeout(30);
    }

    public function isReady(): bool
    {
        try {
            $response = $this->client()->get('/status');
            return $response->successful() && $response->json('ready', false);
        } catch (\Exception $e) {
            Log::error("WA Gateway Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim Pesan Teks
     */
    public function sendText(string $target, string $message): array
    {
        Log::info("[WA Service] Sending text message to target: {$target}");

        try {
            $response = $this->client()->post('/send-text', [
                'target'  => $target,
                'message' => $message,
            ]);

            $isSuccess = $response->successful() && $response->json('success', false);
            $messageId = $response->json('message_id');
            $error     = $response->json('error') ?? $response->json('message') ?? $response->body();

            if ($isSuccess && !empty($messageId)) {
                Log::info("[WA Service SUCCESS] Message sent to {$target} | Message ID: {$messageId}");
                return [
                    'success'    => true,
                    'message_id' => $messageId,
                ];
            }

            Log::error("[WA Service FAIL] Failed to send text to {$target} | HTTP Status: {$response->status()} | Error: {$error}");
            return [
                'success' => false,
                'error'   => $error,
            ];

        } catch (\Exception $e) {
            Log::error("[WA Service EXCEPTION] sendText failed for target {$target}: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Hapus Pesan Teks di WhatsApp
     */
    public function deleteText(string $messageId): array
    {
        Log::info("[WA Service] Requesting message deletion | Message ID: {$messageId}");

        try {
            $response = $this->client()->post('/delete-text', [
                'message_id' => $messageId,
            ]);

            $isSuccess = $response->successful() && $response->json('success', false);
            $error     = $response->json('error') ?? $response->json('message') ?? $response->body();

            if ($isSuccess) {
                Log::info("[WA Service SUCCESS] Message ID {$messageId} successfully deleted on WhatsApp.");
                return ['success' => true];
            }

            Log::error("[WA Service FAIL] Failed to delete Message ID {$messageId} | HTTP Status: {$response->status()} | Error: {$error}");
            return [
                'success' => false,
                'error'   => $error,
            ];

        } catch (\Exception $e) {
            Log::error("[WA Service EXCEPTION] deleteText failed for Message ID {$messageId}: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function sendMedia($target, $filePath, $caption = '')
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->baseUrl($this->baseUrl)->post('/send-media', [
                'target'     => $target,
                'base64Data' => base64_encode(file_get_contents($filePath)),
                'filename'   => basename($filePath),
                'mimeType'   => mime_content_type($filePath),
                'caption'    => $caption,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("[WA Service EXCEPTION] sendMedia failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getGroups(): array
    {
        try {
            $response = $this->client()->get('/groups');
            return $response->successful() ? $response->json('data', []) : [];
        } catch (\Exception $e) {
            Log::error("[WA Service EXCEPTION] getGroups failed: " . $e->getMessage());
            return [];
        }
    }
}
