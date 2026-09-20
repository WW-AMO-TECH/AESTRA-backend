<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    private $baseUrl;
    private $secret;

    public function __construct()
    {
        $this->baseUrl = config('services.paystack.url');
        $this->secret = config('services.paystack.secret');
    }

    public function initializePayment($data)
    {
        $payload = [
            'email' => $data['email'],
            'amount' => (int) round((float) $data['amount'] * 100),
            'callback_url' => $data['callback_url'],
            'metadata' => $data['metadata'] ?? [],
        ];

        Log::info('PAYSTACK INITIALIZATION REQUEST', [
            'url' => $this->baseUrl . '/transaction/initialize',
            'payload' => $payload,
        ]);

        $response = Http::withToken($this->secret)->post(
            $this->baseUrl . '/transaction/initialize',
            $payload
        );

        Log::info('PAYSTACK INITIALIZATION RESPONSE', [
            'http_status' => $response->status(),
            'response' => $response->json(),
        ]);

        return $response->json();
    }

    public function verifyPayment($reference)
    {
        $response = Http::withToken($this->secret)
            ->get($this->baseUrl . '/transaction/verify/' . $reference);

        Log::info('PAYSTACK VERIFICATION RESPONSE', [
            'reference' => $reference,
            'http_status' => $response->status(),
            'response' => $response->json(),
        ]);

        return $response->json();
    }
}