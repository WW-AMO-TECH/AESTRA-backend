<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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
        return Http::withToken($this->secret)->post($this->baseUrl . '/transaction/initialize', [
            'email' => $data['email'],
            'amount' => $data['amount'] * 100, // Paystack uses kobo
            'callback_url' => $data['callback_url'],
            'metadata' => $data['metadata'] ?? [],
        ])->json();
    }

    public function verifyPayment($reference)
    {
        return Http::withToken($this->secret)
            ->get($this->baseUrl . '/transaction/verify/' . $reference)
            ->json();
    }
}