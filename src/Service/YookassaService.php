<?php

declare(strict_types=1);

namespace Rent\Service;

use RuntimeException;

class YookassaService
{
    public function __construct(private readonly array $config)
    {
    }

    public function createPayment(float $amount, string $description, string $returnUrl, array $metadata): array
    {
        $payload = [
            'amount' => [
                'value' => format_money($amount),
                'currency' => (string) app_config($this->config, 'yookassa.currency', 'RUB'),
            ],
            'capture' => true,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $returnUrl,
            ],
            'description' => $description,
            'metadata' => $metadata,
        ];

        return $this->request('POST', '/v3/payments', $payload, bin2hex(random_bytes(16)));
    }

    public function getPayment(string $paymentId): array
    {
        return $this->request('GET', '/v3/payments/' . urlencode($paymentId), null, null);
    }

    private function request(string $method, string $path, ?array $payload, ?string $idempotenceKey): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP extension curl is required for YooKassa integration.');
        }

        $url = 'https://api.yookassa.ru' . $path;
        $shopId = (string) app_config($this->config, 'yookassa.shop_id');
        $secret = (string) app_config($this->config, 'yookassa.secret_key');

        if ($shopId === '' || $secret === '' || $shopId === 'CHANGE_ME' || $secret === 'CHANGE_ME') {
            throw new RuntimeException('ЮKassa credentials are not configured.');
        }

        $ch = curl_init($url);
        $headers = ['Content-Type: application/json'];
        if ($idempotenceKey !== null) {
            $headers[] = 'Idempotence-Key: ' . $idempotenceKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERPWD => $shopId . ':' . $secret,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('YooKassa request failed: ' . $error);
        }

        curl_close($ch);

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid YooKassa response.');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = $decoded['description'] ?? ($decoded['message'] ?? 'YooKassa API error');
            throw new RuntimeException((string) $message);
        }

        return $decoded;
    }
}
