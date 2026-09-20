<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerPayout;
use App\Models\WalletSetting;
use App\Models\WalletTransaction;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paystack;

    private const PAYSTACK_FEE_PERCENTAGE = 2;
    private const BANK_TRANSFER_FEE_PERCENTAGE = 0.7;

    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }

    public function initialize(Request $request)
    {
        try {
            if (!auth()->check()) {
                return response()->json([
                    'message' => 'Please login first.',
                ], 401);
            }

            $request->validate([
                'email' => 'required|email',
                'payment_method' => 'required|in:paystack',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'fulfillment' => 'required|in:delivery,pickup',
                'full_name' => 'required|string',
                'phone' => 'required|string',
            ]);

            $user = auth()->user();

            $productIds = collect($request->items)
                ->pluck('product_id')
                ->unique();

            $products = Product::whereIn('id', $productIds)
                ->where('status', true)
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $items = [];

            foreach ($request->items as $item) {
                $product = $products->get($item['product_id']);

                if (!$product) {
                    return response()->json([
                        'message' => 'One or more products are unavailable.',
                    ], 422);
                }

                $quantity = (int) $item['quantity'];

                if ($product->stock < $quantity) {
                    return response()->json([
                        'message' => "{$product->name} does not have enough stock.",
                    ], 422);
                }

                $originalPrice = (float) ($product->original_price ?? $product->price ?? 0);
                $discountPercentage = min(
                    100,
                    max(0, (float) ($product->discount_percentage ?? 0))
                );

                $price = round(
                    $originalPrice -
                    ($originalPrice * $discountPercentage / 100),
                    2
                );

                if ($price < 0) {
                    $price = 0;
                }

                $subtotal += $price * $quantity;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'original_price' => $originalPrice,
                    'discount_percentage' => $discountPercentage,
                ];
            }

            $subtotal = round($subtotal, 2);

            if ($subtotal <= 0) {
                return response()->json([
                    'message' => 'Invalid checkout amount.',
                ], 422);
            }

            $transactionFeePercentage = self::PAYSTACK_FEE_PERCENTAGE;
            $transactionFee = round(
                $subtotal * ($transactionFeePercentage / 100),
                2
            );

            $checkoutAmount = round(
                $subtotal + $transactionFee,
                2
            );

            $metadata = [
                'user_id' => $user->id,
                'items' => $items,
                'checkout_subtotal' => $subtotal,
                'transaction_fee' => $transactionFee,
                'transaction_fee_percentage' => $transactionFeePercentage,
                'checkout_amount' => $checkoutAmount,
                'fulfillment' => $request->fulfillment,
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'state' => $request->state,
                'city' => $request->city,
                'address' => $request->address,
                'pickup_state' => $request->pickup_state,
                'pickup_location' => $request->pickup_location,
            ];

            Log::info('PAYSTACK CHECKOUT SNAPSHOT CREATED', [
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'transaction_fee' => $transactionFee,
                'transaction_fee_percentage' => $transactionFeePercentage,
                'checkout_amount' => $checkoutAmount,
                'items' => $items,
            ]);

            $response = $this->paystack->initializePayment([
                'email' => $request->email,
                'amount' => $checkoutAmount,
                'metadata' => $metadata,
                'callback_url' => url('/api/payments/callback'),
            ]);

            if (!($response['status'] ?? false)) {
                $message = $response['message']
                    ?? data_get($response, 'error.message')
                    ?? 'Unable to initialize Paystack payment.';

                Log::warning('PAYSTACK INITIALIZATION FAILED', [
                    'user_id' => $user->id,
                    'message' => $message,
                    'response' => $response,
                ]);

                return response()->json([
                    'message' => $message,
                    'payment_failed' => true,
                ], 422);
            }

            if (
                empty($response['data']['authorization_url']) ||
                empty($response['data']['reference'])
            ) {
                Log::error('PAYSTACK INITIALIZATION RESPONSE INVALID', [
                    'user_id' => $user->id,
                    'response' => $response,
                ]);

                return response()->json([
                    'message' => 'Unable to initialize Paystack payment.',
                    'payment_failed' => true,
                ], 422);
            }

            return response()->json([
                'authorization_url' => $response['data']['authorization_url'],
                'reference' => $response['data']['reference'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('PAYSTACK INIT ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Unable to initialize payment.',
                'payment_failed' => true,
            ], 500);
        }
    }

    public function submitBankTransfer(Request $request)
    {
        try {
            if (!auth()->check()) {
                return response()->json([
                    'message' => 'Please login first.',
                ], 401);
            }

            $request->validate([
                'email' => 'required|email',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'fulfillment' => 'required|in:delivery,pickup',
                'full_name' => 'required|string',
                'phone' => 'required|string',
                'bank_account_name' => 'required|string|max:255',
                'bank_transaction_number' => 'required|string|max:255',
            ]);

            $user = auth()->user();

            $productIds = collect($request->items)
                ->pluck('product_id')
                ->unique();

            $products = Product::whereIn('id', $productIds)
                ->where('status', true)
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $items = [];

            foreach ($request->items as $item) {
                $product = $products->get($item['product_id']);

                if (!$product) {
                    return response()->json([
                        'message' => 'One or more products are unavailable.',
                    ], 422);
                }

                $quantity = (int) $item['quantity'];

                if ($product->stock < $quantity) {
                    return response()->json([
                        'message' => "{$product->name} does not have enough stock.",
                    ], 422);
                }

                $originalPrice = (float) ($product->original_price ?? $product->price ?? 0);
                $discountPercentage = min(
                    100,
                    max(0, (float) ($product->discount_percentage ?? 0))
                );

                $price = round(
                    $originalPrice -
                    ($originalPrice * $discountPercentage / 100),
                    2
                );

                if ($price < 0) {
                    $price = 0;
                }

                $subtotal += $price * $quantity;

                $items[] = [
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'quantity' => $quantity,
                    'price' => $price,
                ];
            }

            $subtotal = round($subtotal, 2);

            if ($subtotal <= 0) {
                return response()->json([
                    'message' => 'Invalid checkout amount.',
                ], 422);
            }

            $transactionFeePercentage = self::BANK_TRANSFER_FEE_PERCENTAGE;

            $transactionFee = round(
                $subtotal * ($transactionFeePercentage / 100),
                2
            );

            $total = round(
                $subtotal + $transactionFee,
                2
            );

            $reference = 'BT-' . strtoupper(uniqid());

            $order = DB::transaction(function () use (
                $user,
                $request,
                $items,
                $subtotal,
                $transactionFee,
                $transactionFeePercentage,
                $total,
                $reference
            ) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => 'ORD-' . strtoupper(uniqid()),
                    'reference' => $reference,
                    'fulfillment' => $request->fulfillment,
                    'full_name' => $request->full_name,
                    'phone' => $request->phone,
                    'state' => $request->state,
                    'city' => $request->city,
                    'address' => $request->address,
                    'pickup_state' => $request->pickup_state,
                    'pickup_location' => $request->pickup_location,
                    'payment_method' => 'bank_transfer',
                    'payment_status' => 'pending',
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'transaction_fee' => $transactionFee,
                    'transaction_fee_percentage' => $transactionFeePercentage,
                    'total' => $total,
                    'bank_account_name' => trim($request->bank_account_name),
                    'bank_transaction_number' => trim($request->bank_transaction_number),
                ]);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'seller_id' => $item['seller_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }

                return $order;
            });

            Log::info('BANK TRANSFER ORDER SUBMITTED', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reference' => $reference,
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'transaction_fee' => $transactionFee,
                'total' => $total,
                'bank_account_name' => $request->bank_account_name,
                'bank_transaction_number' => $request->bank_transaction_number,
            ]);

            return response()->json([
                'status' => 'pending',
                'message' => 'Bank transfer details submitted successfully. Your payment will be verified before the order is processed.',
                'reference' => $reference,
                'order_number' => $order->order_number,
                'total' => $total,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('BANK TRANSFER SUBMISSION ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Unable to submit bank transfer details.',
            ], 500);
        }
    }

    public function verifyBankTransfer(Request $request, $id)
    {
        try {
            $order = DB::transaction(function () use ($id) {
                $order = Order::where('id', $id)
                    ->where('payment_method', 'bank_transfer')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \RuntimeException('Bank transfer order not found.');
                }

                if ($order->payment_status === 'paid') {
                    return $order->load('items');
                }

                if ($order->payment_status !== 'pending') {
                    throw new \RuntimeException('This bank transfer cannot be verified.');
                }

                $orderItems = $order->items()
                    ->lockForUpdate()
                    ->get();

                if ($orderItems->isEmpty()) {
                    throw new \RuntimeException('This order has no items.');
                }

                $productIds = $orderItems
                    ->pluck('product_id')
                    ->unique()
                    ->sort()
                    ->values();

                $products = Product::whereIn('id', $productIds)
                    ->where('status', true)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($orderItems as $item) {
                    $product = $products->get($item->product_id);

                    if (!$product) {
                        throw new \RuntimeException(
                            "Product #{$item->product_id} is no longer available."
                        );
                    }

                    if ($product->stock < $item->quantity) {
                        throw new \RuntimeException(
                            "{$product->name} does not have enough stock."
                        );
                    }
                }

                foreach ($orderItems as $item) {
                    Product::where('id', $item->product_id)
                        ->decrement('stock', $item->quantity);
                }

                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);

                $paymentReference = $order->reference;

                WalletTransaction::firstOrCreate(
                    ['reference' => $paymentReference],
                    [
                        'order_id' => $order->id,
                        'seller_id' => null,
                        'type' => 'payment',
                        'amount' => (float) $order->total,
                        'status' => 'completed',
                        'description' => "Customer bank transfer payment for order {$order->order_number}",
                    ]
                );

                $commissionPercentage = (float) (
                    WalletSetting::first()?->commission_percentage ?? 5
                );

                $sellerTotals = $orderItems
                    ->groupBy('seller_id')
                    ->map(function ($items) {
                        return collect($items)->sum(function ($item) {
                            return (float) $item->price *
                                (int) $item->quantity;
                        });
                    });

                foreach ($sellerTotals as $sellerId => $grossAmount) {
                    if (!$sellerId) {
                        continue;
                    }

                    $grossAmount = round($grossAmount, 2);

                    $commissionAmount = round(
                        $grossAmount * ($commissionPercentage / 100),
                        2
                    );

                    $payoutAmount = round(
                        $grossAmount - $commissionAmount,
                        2
                    );

                    SellerPayout::firstOrCreate(
                        [
                            'seller_id' => $sellerId,
                            'order_id' => $order->id,
                        ],
                        [
                            'gross_amount' => $grossAmount,
                            'commission_amount' => $commissionAmount,
                            'payout_amount' => $payoutAmount,
                            'status' => 'pending',
                        ]
                    );

                    WalletTransaction::firstOrCreate(
                        [
                            'reference' => "EARN-{$order->id}-{$sellerId}",
                        ],
                        [
                            'order_id' => $order->id,
                            'seller_id' => $sellerId,
                            'type' => 'platform_earning',
                            'amount' => $commissionAmount,
                            'status' => 'completed',
                            'description' => "AESTRA commission from order {$order->order_number}",
                        ]
                    );
                }

                WalletTransaction::firstOrCreate(
                    [
                        'reference' => "FEE-{$order->id}",
                    ],
                    [
                        'order_id' => $order->id,
                        'seller_id' => null,
                        'type' => 'platform_earning',
                        'amount' => (float) $order->transaction_fee,
                        'status' => 'completed',
                        'description' => "AESTRA transaction fee from order {$order->order_number}",
                    ]
                );

                Cart::where('user_id', $order->user_id)->delete();

                return $order->load('items');
            });

            Log::info('BANK TRANSFER VERIFIED SUCCESSFULLY', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reference' => $order->reference,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bank transfer verified and order processed successfully.',
                'order' => $order,
            ]);
        } catch (\Throwable $e) {
            Log::error('BANK TRANSFER VERIFICATION ERROR', [
                'order_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function callback(Request $request)
    {
        try {
            $reference = $request->query('reference');

            if (!$reference) {
                return redirect(
                    env('FRONTEND_URL') . '/payment-failed?message=' .
                    urlencode('Payment reference was missing.')
                );
            }

            $response = $this->paystack->verifyPayment($reference);

            if (!($response['status'] ?? false)) {
                return redirect(
                    env('FRONTEND_URL') . '/payment-failed?message=' .
                    urlencode('Payment could not be verified.')
                );
            }

            $paymentData = $response['data'] ?? null;

            if (
                !$paymentData ||
                ($paymentData['status'] ?? null) !== 'success'
            ) {
                return redirect(
                    env('FRONTEND_URL') . '/payment-failed?message=' .
                    urlencode('Payment was not successful.')
                );
            }

            $order = $this->processSuccessfulPayment(
                $reference,
                $paymentData
            );

            if (!$order) {
                return redirect(
                    env('FRONTEND_URL') . '/payment-failed?message=' .
                    urlencode('Payment was received, but we could not finalize the order. Please contact support before making another payment.')
                );
            }

            return redirect(
                env('FRONTEND_URL') .
                "/payment-success?reference=" .
                urlencode($reference)
            );
        } catch (\Throwable $e) {
            Log::error('Payment callback failed', [
                'message' => $e->getMessage(),
                'reference' => $request->query('reference'),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect(
                env('FRONTEND_URL') . '/payment-failed?message=' .
                urlencode('Payment was received, but order finalization failed. Please contact support before making another payment.')
            );
        }
    }

    public function verifyPayment(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Missing reference.',
            ], 400);
        }

        try {
            $response = $this->paystack->verifyPayment($reference);

            if (!($response['status'] ?? false)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment could not be verified.',
                ], 422);
            }

            $paymentData = $response['data'] ?? null;

            if (
                !$paymentData ||
                ($paymentData['status'] ?? null) !== 'success'
            ) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment was not successful.',
                ], 422);
            }

            $order = $this->processSuccessfulPayment(
                $reference,
                $paymentData
            );

            if (!$order) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment was successful but the order could not be finalized. Please contact support before making another payment.',
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'success' => true,
                'reference' => $reference,
                'order' => $order->order_number,
            ]);
        } catch (\Throwable $e) {
            Log::error('PAYSTACK VERIFY ERROR', [
                'message' => $e->getMessage(),
                'reference' => $reference,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Payment verification failed.',
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $signature = $request->header('x-paystack-signature');

        if (
            !$signature ||
            !hash_equals(
                hash_hmac(
                    'sha512',
                    $request->getContent(),
                    env('PAYSTACK_SECRET_KEY')
                ),
                $signature
            )
        ) {
            return response()->json([
                'message' => 'Invalid signature',
            ], 401);
        }

        try {
            $event = $request->all();

            if (($event['event'] ?? null) !== 'charge.success') {
                return response()->json([
                    'ok' => true,
                ]);
            }

            $payment = $event['data'] ?? [];
            $reference = $payment['reference'] ?? null;

            if (!$reference) {
                return response()->json([
                    'ok' => true,
                ]);
            }

            $this->processSuccessfulPayment(
                $reference,
                $payment
            );

            return response()->json([
                'ok' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('PAYSTACK WEBHOOK ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    private function processSuccessfulPayment(
        string $reference,
        array $paymentData
    ): ?Order {
        return DB::transaction(function () use ($reference, $paymentData) {
            $existingOrder = Order::where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if ($existingOrder) {
                Log::info('PAYSTACK ORDER ALREADY EXISTS', [
                    'reference' => $reference,
                    'order_id' => $existingOrder->id,
                    'order_number' => $existingOrder->order_number,
                ]);

                return $existingOrder->load('items');
            }

            $metadata = $paymentData['metadata'] ?? [];

            if (is_string($metadata)) {
                $decodedMetadata = json_decode($metadata, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $metadata = $decodedMetadata;
                }
            }

            if (!is_array($metadata)) {
                Log::error('PAYSTACK METADATA INVALID', [
                    'reference' => $reference,
                    'metadata' => $metadata,
                ]);

                return null;
            }

            $userId = data_get($metadata, 'user_id');
            $items = data_get($metadata, 'items', []);
            $checkoutSubtotal = (float) data_get(
                $metadata,
                'checkout_subtotal',
                0
            );
            $transactionFee = (float) data_get(
                $metadata,
                'transaction_fee',
                0
            );
            $transactionFeePercentage = (float) data_get(
                $metadata,
                'transaction_fee_percentage',
                self::PAYSTACK_FEE_PERCENTAGE
            );
            $checkoutAmount = (float) data_get(
                $metadata,
                'checkout_amount',
                0
            );

            if (
                !$userId ||
                !is_array($items) ||
                count($items) === 0 ||
                $checkoutSubtotal <= 0 ||
                $checkoutAmount <= 0
            ) {
                Log::error('PAYSTACK PAYMENT MISSING METADATA', [
                    'reference' => $reference,
                    'metadata' => $metadata,
                ]);

                return null;
            }

            $requestedAmount = round(
                ((float) (
                    $paymentData['requested_amount']
                    ?? $paymentData['amount']
                    ?? 0
                )) / 100,
                2
            );

            $customerPaidAmount = round(
                ((float) ($paymentData['amount'] ?? 0)) / 100,
                2
            );

            if (abs($requestedAmount - $checkoutAmount) > 0.01) {
                Log::warning('PAYSTACK CHECKOUT AMOUNT MISMATCH', [
                    'reference' => $reference,
                    'requested_amount' => $requestedAmount,
                    'customer_paid_amount' => $customerPaidAmount,
                    'checkout_amount' => $checkoutAmount,
                    'paystack_fee' => round(
                        $customerPaidAmount - $requestedAmount,
                        2
                    ),
                ]);

                return null;
            }

            $productIds = collect($items)
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values();

            $products = Product::whereIn('id', $productIds)
                ->where('status', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $productId = (int) data_get($item, 'product_id');
                $quantity = (int) data_get($item, 'quantity');
                $price = (float) data_get($item, 'unit_price', 0);

                $product = $products->get($productId);

                if (!$product || $quantity < 1 || $price < 0) {
                    Log::warning('PAYSTACK ORDER PRODUCT INVALID', [
                        'reference' => $reference,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => $price,
                    ]);

                    return null;
                }

                if ($product->stock < $quantity) {
                    Log::warning('PAYSTACK ORDER STOCK UNAVAILABLE', [
                        'reference' => $reference,
                        'product_id' => $product->id,
                        'stock' => $product->stock,
                        'requested' => $quantity,
                    ]);

                    return null;
                }

                $price = round($price, 2);
                $lineTotal = round($price * $quantity, 2);
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'quantity' => $quantity,
                    'price' => $price,
                ];
            }

            $subtotal = round($subtotal, 2);

            if (abs($subtotal - $checkoutSubtotal) > 0.01) {
                Log::warning('PAYSTACK SNAPSHOT SUBTOTAL MISMATCH', [
                    'reference' => $reference,
                    'checkout_subtotal' => $checkoutSubtotal,
                    'calculated_subtotal' => $subtotal,
                ]);

                return null;
            }

            $calculatedTransactionFee = round(
                $subtotal * ($transactionFeePercentage / 100),
                2
            );

            if (abs($calculatedTransactionFee - $transactionFee) > 0.01) {
                Log::warning('PAYSTACK TRANSACTION FEE MISMATCH', [
                    'reference' => $reference,
                    'metadata_fee' => $transactionFee,
                    'calculated_fee' => $calculatedTransactionFee,
                ]);

                return null;
            }

            $calculatedCheckoutAmount = round(
                $subtotal + $transactionFee,
                2
            );

            if (abs($calculatedCheckoutAmount - $checkoutAmount) > 0.01) {
                Log::warning('PAYSTACK CHECKOUT TOTAL MISMATCH', [
                    'reference' => $reference,
                    'checkout_amount' => $checkoutAmount,
                    'calculated_total' => $calculatedCheckoutAmount,
                ]);

                return null;
            }

            if (abs($requestedAmount - $calculatedCheckoutAmount) > 0.01) {
                Log::warning('PAYSTACK REQUESTED TOTAL MISMATCH', [
                    'reference' => $reference,
                    'requested_amount' => $requestedAmount,
                    'calculated_total' => $calculatedCheckoutAmount,
                ]);

                return null;
            }

            $order = Order::create([
                'user_id' => $userId,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'reference' => $reference,
                'fulfillment' => data_get($metadata, 'fulfillment'),
                'full_name' => data_get($metadata, 'full_name'),
                'phone' => data_get($metadata, 'phone'),
                'state' => data_get($metadata, 'state'),
                'city' => data_get($metadata, 'city'),
                'address' => data_get($metadata, 'address'),
                'pickup_state' => data_get($metadata, 'pickup_state'),
                'pickup_location' => data_get($metadata, 'pickup_location'),
                'payment_method' => 'paystack',
                'payment_status' => 'paid',
                'status' => 'processing',
                'subtotal' => $subtotal,
                'transaction_fee' => $transactionFee,
                'transaction_fee_percentage' => $transactionFeePercentage,
                'total' => $checkoutAmount,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'seller_id' => $item['seller_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                Product::where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
            }

            WalletTransaction::firstOrCreate(
                ['reference' => $reference],
                [
                    'order_id' => $order->id,
                    'seller_id' => null,
                    'type' => 'payment',
                    'amount' => $requestedAmount,
                    'status' => 'completed',
                    'description' => "Customer payment for order {$order->order_number}",
                ]
            );

            WalletTransaction::firstOrCreate(
                ['reference' => "FEE-{$order->id}"],
                [
                    'order_id' => $order->id,
                    'seller_id' => null,
                    'type' => 'platform_earning',
                    'amount' => $transactionFee,
                    'status' => 'completed',
                    'description' => "AESTRA transaction fee from order {$order->order_number}",
                ]
            );

            $commissionPercentage = (float) (
                WalletSetting::first()?->commission_percentage ?? 5
            );

            $sellerTotals = collect($orderItems)
                ->groupBy('seller_id')
                ->map(function ($items) {
                    return collect($items)->sum(function ($item) {
                        return (float) $item['price'] *
                            (int) $item['quantity'];
                    });
                });

            foreach ($sellerTotals as $sellerId => $grossAmount) {
                if (!$sellerId) {
                    continue;
                }

                $grossAmount = round($grossAmount, 2);

                $commissionAmount = round(
                    $grossAmount * ($commissionPercentage / 100),
                    2
                );

                $payoutAmount = round(
                    $grossAmount - $commissionAmount,
                    2
                );

                SellerPayout::firstOrCreate(
                    [
                        'seller_id' => $sellerId,
                        'order_id' => $order->id,
                    ],
                    [
                        'gross_amount' => $grossAmount,
                        'commission_amount' => $commissionAmount,
                        'payout_amount' => $payoutAmount,
                        'status' => 'pending',
                    ]
                );

                WalletTransaction::firstOrCreate(
                    [
                        'reference' => "EARN-{$order->id}-{$sellerId}",
                    ],
                    [
                        'order_id' => $order->id,
                        'seller_id' => $sellerId,
                        'type' => 'platform_earning',
                        'amount' => $commissionAmount,
                        'status' => 'completed',
                        'description' => "AESTRA commission from order {$order->order_number}",
                    ]
                );
            }

            Cart::where('user_id', $userId)->delete();

            Log::info('PAYSTACK ORDER FINALIZED SUCCESSFULLY', [
                'reference' => $reference,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $userId,
                'requested_amount' => $requestedAmount,
                'customer_paid_amount' => $customerPaidAmount,
                'transaction_fee' => $transactionFee,
            ]);

            return $order->load('items');
        });
    }
}