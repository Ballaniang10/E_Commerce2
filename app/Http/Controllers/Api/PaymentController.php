<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Mail\PaymentConfirmedMail;
use Illuminate\Support\Facades\Mail;
use App\Services\ActivityLogService;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for Stripe.
     */
    public function createPaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);

        // Check if user owns this order
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if order is already paid
        if ($order->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Order is already paid'
            ], 400);
        }

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($order->total_amount * 100), // Convert to cents
                'currency' => 'eur',
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
            ]);

            // Create or update payment record
            $payment = Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'amount' => $order->total_amount,
                    'method' => 'online',
                    'status' => 'pending',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                ]
            );

            ActivityLogService::logPayment('Payment intent created', $payment, $request->user(), [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total_amount
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment intent creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm payment after successful Stripe payment.
     */
    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);
            
            if ($paymentIntent->status === 'succeeded') {
                $payment = Payment::where('stripe_payment_intent_id', $request->payment_intent_id)->first();
                
                if ($payment) {
                    $payment->update(['status' => 'paid']);
                    $payment->order->update(['payment_status' => 'paid']);

                    ActivityLogService::logPayment('Payment confirmed via Stripe', $payment, $payment->order->user, [
                        'stripe_payment_intent_id' => $request->payment_intent_id,
                        'amount' => $payment->amount
                    ]);

                    // Envoyer l'email de confirmation de paiement
                    Mail::to($payment->order->user->email)->send(new PaymentConfirmedMail($payment->order));

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment confirmed successfully'
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment not found or not successful'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment confirmation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark cash on delivery payment as received (Admin only).
     */
    public function cashOnDelivery(Request $request)
    {
        $this->authorize('update', Payment::class);

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);

        if ($order->payment_method !== 'cash_on_delivery') {
            return response()->json([
                'success' => false,
                'message' => 'Order is not a cash on delivery order'
            ], 400);
        }

        // Create or update payment record
        $payment = Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'amount' => $order->total_amount,
                'method' => 'cash_on_delivery',
                'status' => 'paid',
            ]
        );

        $order->update(['payment_status' => 'paid']);

        ActivityLogService::logPayment('Cash on delivery payment confirmed', $payment, $request->user(), [
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'confirmed_by_admin' => $request->user()->id
        ]);

        // Envoyer l'email de confirmation de paiement
        Mail::to($order->user->email)->send(new PaymentConfirmedMail($order));

        return response()->json([
            'success' => true,
            'message' => 'Cash on delivery payment marked as received'
        ]);
    }
} 