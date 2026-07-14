<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaystackService;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\OrderItem;

class PaymentController extends Controller
{
    protected $paystack;

    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }

    public function initialize(Request $request)
    {
        try {

            if (!auth()->check()) {
                return response()->json(['message' => 'Unauthenticated user'], 401);
            }

            $request->validate([
                'amount' => 'required|numeric|min:1',
                'email' => 'required|email',

                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric',
            ]);

            $user = auth()->user();

            // FIX: ensure column exists in DB
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . time(),

                'fulfillment' => $request->fulfillment,
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'state' => $request->state,
                'city' => $request->city,
                'address' => $request->address,

                'pickup_state' => $request->pickup_state,
                'pickup_location' => $request->pickup_location,

                'payment_method' => 'paystack',
                'payment_status' => 'pending',
                'status' => 'pending',

                'subtotal' => $request->amount,
                'total' => $request->amount,
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
            }

            $amount = (float) $request->amount * 1;

            $response = $this->paystack->initializePayment([
                'email' => $request->email,
                'amount' => $amount,

                'metadata' => [
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                ],

                'callback_url' => url('/api/payments/callback'),
            ]);

            if (!($response['status'] ?? false)) {
                return response()->json([
                    'message' => 'Paystack failed',
                    'error' => $response
                ], 500);
            }

            return response()->json([
                'authorization_url' => $response['data']['authorization_url'],
                'reference' => $response['data']['reference'],
            ]);

        } catch (\Throwable $e) {

            \Log::error('PAYSTACK INIT ERROR', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $reference = $request->query('reference');

            if (!$reference) {
                return redirect('http://localhost:5173/payment-failed');
            }

            // VERIFY PAYMENT
            $response = $this->paystack->verifyPayment($reference);

            // HARD SAFETY CHECK
            if (!isset($response['status']) || $response['status'] !== true) {
                return redirect('http://localhost:5173/payment-failed');
            }

            $paymentData = $response['data'] ?? null;

            if (!$paymentData || $paymentData['status'] !== 'success') {
                return redirect('http://localhost:5173/payment-failed');
            }

            $metadata = $paymentData['metadata'] ?? [];

            $orderNumber = $metadata['order_number'] ?? null;

            $order = Order::where('order_number', $orderNumber)->first();

            if ($order) {
                $order->update([
                    'reference' => $reference,
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);
            }

            // TODO: Save order to DB here
            // Order::create([...])

            return redirect("http://localhost:5173/payment-success?reference={$reference}");

        } catch (\Throwable $e) {
            // LOG REAL ERROR (VERY IMPORTANT)
            \Log::error('Payment callback failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('http://localhost:5173/payment-failed');
        }
    }

    public function verifyPayment(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return response()->json(['error' => 'Missing reference'], 400);
        }

        // Call Paystack
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->get("https://api.paystack.co/transaction/verify/" . $reference);

        $data = $response->json();

        if (!$data['status']) {
            return response()->json(['status' => 'failed']);
        }

        return response()->json([
            'status' => 'success',
            'order' => null // optional: you can return order if you mapped reference → order
        ]);
    }

    // Paystack webhook (recommended)
    public function handleWebhook(Request $request)
    {
        $data = $request->all();

        if ($data['event'] !== 'charge.success') {
            return response()->json(['ok' => true]);
        }

        $payment = $data['data'];
        $reference = $payment['reference'];

        $metadata = $payment['metadata'];

        $order = Order::where('order_number', $metadata['order_number'])->first();

        if (!$order) {
            return response()->json(['ok' => true]);
        }

        // prevent duplicates
        if ($order->payment_status === 'paid') {
            return response()->json(['ok' => true]);
        }

        $order->update([
            'reference' => $reference,
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        return response()->json(['ok' => true]);
    }
}