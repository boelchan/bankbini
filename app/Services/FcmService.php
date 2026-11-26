<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FcmToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use OpenSSLAsymmetricKey;
use Throwable;

/**
 * Mengirim notifikasi web push melalui Firebase Cloud Messaging HTTP v1.
 */
class FcmService
{
    /**
     * Menentukan apakah konfigurasi FCM sudah cukup untuk mengirim notifikasi.
     */
    public function isReady(): bool
    {
        if (! (bool) config('services.fcm.enabled')) {
            return false;
        }

        $account = $this->serviceAccount();

        return $account !== null && $this->projectId($account) !== null;
    }

    /**
     * Mengirim satu notifikasi ke seluruh token yang diberikan.
     *
     * @param  array<int, string>  $tokens
     */
    public function send(array $tokens, string $title, string $body, string $url): int
    {
        if ($tokens === [] || ! $this->isReady()) {
            return 0;
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return 0;
        }

        $projectId = $this->projectId($this->serviceAccount() ?? []);
        if ($projectId === null) {
            return 0;
        }

        $sent = 0;

        foreach (array_unique($tokens) as $token) {
            if ($this->sendToToken($accessToken, $projectId, $token, $title, $body, $url)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Mengirim notifikasi ke satu token FCM.
     */
    private function sendToToken(
        string $accessToken,
        string $projectId,
        string $token,
        string $title,
        string $body,
        string $url
    ): bool {
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'url' => $url,
                ],
                'webpush' => [
                    'headers' => [
                        'TTL' => (string) config('services.fcm.ttl_seconds', 10800),
                    ],
                    'notification' => [
                        'icon' => asset('favicon/favicon-96x96.png'),
                        'badge' => asset('favicon/favicon-96x96.png'),
                        'data' => [
                            'url' => $url,
                        ],
                    ],
                ],
            ],
        ];

        if (str_starts_with($url, 'https://')) {
            $payload['message']['webpush']['fcm_options'] = ['link' => $url];
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);
        } catch (Throwable $exception) {
            Log::warning('Gagal menghubungi FCM.', ['error' => $exception->getMessage()]);

            return false;
        }

        if ($response->successful()) {
            return true;
        }

        $bodyResponse = $response->body();
        $isUnregistered = $response->status() === 404 || str_contains($bodyResponse, 'UNREGISTERED');

        if ($isUnregistered) {
            FcmToken::query()->where('token', $token)->delete();

            return false;
        }

        Log::warning('FCM menolak pengiriman notifikasi.', [
            'status' => $response->status(),
            'token_prefix' => substr($token, 0, 8),
            'response' => substr($bodyResponse, 0, 300),
        ]);

        return false;
    }

    /**
     * Mengambil access token OAuth2 dari cache atau memintanya ke Google.
     */
    private function accessToken(): ?string
    {
        $cached = Cache::get('fcm.access_token');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $account = $this->serviceAccount();
        if ($account === null) {
            return null;
        }

        $jwt = $this->buildJwt($account);
        if ($jwt === null) {
            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Gagal meminta access token FCM.', ['error' => $exception->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Google menolak permintaan access token FCM.', [
                'status' => $response->status(),
                'response' => substr($response->body(), 0, 300),
            ]);

            return null;
        }

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            return null;
        }

        $expiresIn = max(60, (int) $response->json('expires_in', 3600) - 60);
        Cache::put('fcm.access_token', $token, now()->addSeconds($expiresIn));

        return $token;
    }

    /**
     * Membuat JWT RS256 untuk pertukaran access token Google.
     *
     * @param  array<string, mixed>  $account
     */
    private function buildJwt(array $account): ?string
    {
        $privateKey = $this->privateKey($account);
        $clientEmail = (string) ($account['client_email'] ?? '');

        if ($privateKey === null || $clientEmail === '') {
            Log::warning('Service account FCM tidak memiliki private key atau client email.');

            return null;
        }

        $issuedAt = time();
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => (string) ($account['private_key_id'] ?? ''),
        ];
        $claims = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ];

        try {
            $unsigned = $this->base64Url(json_encode($header, JSON_THROW_ON_ERROR))
                .'.'
                .$this->base64Url(json_encode($claims, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            Log::warning('Gagal menyusun header JWT FCM.', ['error' => $exception->getMessage()]);

            return null;
        }

        $signature = '';
        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::warning('Gagal menandatangani JWT FCM.');

            return null;
        }

        return $unsigned.'.'.$this->base64Url($signature);
    }

    /**
     * Membaca dan memvalidasi file service account FCM.
     *
     * @return array<string, mixed>|null
     */
    private function serviceAccount(): ?array
    {
        static $account = null;

        if (is_array($account)) {
            return $account;
        }

        $configuredPath = trim((string) config('services.fcm.service_account_path'));
        if ($configuredPath === '') {
            return null;
        }

        $path = $this->resolvePath($configuredPath);
        if ($path === null || ! is_file($path)) {
            Log::warning('File service account FCM tidak ditemukan.', ['path' => $configuredPath]);

            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('File service account FCM bukan JSON valid.', ['error' => $exception->getMessage()]);

            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $account = $decoded;

        return $account;
    }

    /**
     * Menyelesaikan lokasi file service account dari path absolut atau relatif.
     */
    private function resolvePath(string $path): ?string
    {
        if (is_file($path)) {
            return $path;
        }

        $candidates = [
            base_path($path),
            storage_path('app/private/'.$path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Mengambil kunci privat dari service account FCM.
     *
     * @param  array<string, mixed>  $account
     */
    private function privateKey(array $account): ?OpenSSLAsymmetricKey
    {
        $key = $account['private_key'] ?? null;
        if (! is_string($key) || $key === '') {
            return null;
        }

        $resource = openssl_pkey_get_private($key);

        return $resource instanceof OpenSSLAsymmetricKey ? $resource : null;
    }

    /**
     * Mengambil project ID dari konfigurasi atau service account.
     *
     * @param  array<string, mixed>  $account
     */
    private function projectId(array $account): ?string
    {
        $configured = trim((string) config('services.fcm.project_id'));
        if ($configured !== '') {
            return $configured;
        }

        $fromAccount = trim((string) ($account['project_id'] ?? ''));

        return $fromAccount !== '' ? $fromAccount : null;
    }

    /**
     * Mengencode data ke format base64 URL-safe.
     */
    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
