<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PawaPayService
{
    public function baseUrl(): string
    {
        $environment = get_payment_settings('pawapay_environment');

        if ($environment === 'production') {
            $baseUrl = rtrim((string) get_payment_settings('pawapay_live_base_url'), '/');
            return preg_replace('#/v1$#', '', $baseUrl) ?? $baseUrl;
        }

        $baseUrl = rtrim((string) get_payment_settings('pawapay_test_base_url'), '/');
        return preg_replace('#/v1$#', '', $baseUrl) ?? $baseUrl;
    }

    public function apiKey(): string
    {
        $environment = get_payment_settings('pawapay_environment');

        if ($environment === 'production') {
            return (string) get_payment_settings('pawapay_live_api_key');
        }

        return (string) get_payment_settings('pawapay_test_api_key');
    }

    public function createDeposit(array $payload): Response
    {
        $baseUrl = $this->baseUrl();
        $apiKey = $this->apiKey();

        return Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])
            ->post($baseUrl . '/v1/deposits', $payload);
    }

    public function predictCorrespondent(string $msisdn): Response
    {
        $baseUrl = $this->baseUrl();
        $apiKey = $this->apiKey();

        return Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])
            ->post($baseUrl . '/v1/predict-correspondent', [
                'msisdn' => $msisdn,
            ]);
    }

    public function getDeposit(string $depositId): Response
    {
        $baseUrl = $this->baseUrl();
        $apiKey = $this->apiKey();

        return Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])
            ->get($baseUrl . '/v1/deposits/' . urlencode($depositId));
    }
}
