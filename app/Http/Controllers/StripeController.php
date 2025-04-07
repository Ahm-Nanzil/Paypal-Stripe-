<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class StripeController extends Controller
{
    /**
     * Display the Stripe payment page
     */
    public function index()
    {
        return view('stripe.index');
    }

    /**
     * Create a Stripe checkout session
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        // Set Stripe API key
        Stripe::setApiKey(config('stripe.secret'));

        try {
            // Create a checkout session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Payment',
                                'description' => 'Payment to our site',
                            ],
                            'unit_amount' => $request->amount * 100, // Stripe uses cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('stripe.cancel'),
            ]);

            return redirect()->away($session->url);
        } catch (ApiErrorException $e) {
            return redirect()->route('stripe.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request)
    {
        if (!$request->session_id) {
            return redirect()->route('stripe.index')
                ->with('error', 'No session ID provided.');
        }

        Stripe::setApiKey(config('stripe.secret'));

        try {
            $session = Session::retrieve($request->session_id);
            $payment_intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);

            // Save transaction to database
            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'transaction_id' => $payment_intent->id,
                'payment_method' => 'stripe',
                'amount' => $payment_intent->amount / 100, // Convert cents to dollars
                'currency' => $payment_intent->currency,
                'status' => $payment_intent->status,
                'payment_data' => [
                    'session_id' => $session->id,
                    'payment_intent' => $payment_intent->id,
                    'payment_method' => $payment_intent->payment_method,
                    'payment_status' => $payment_intent->status,
                    'amount_received' => $payment_intent->amount_received / 100,
                ],
            ]);

            return view('stripe.success', [
                'payment' => [
                    'id' => $payment_intent->id,
                    'status' => $payment_intent->status,
                    'amount' => $payment_intent->amount / 100,
                    'currency' => strtoupper($payment_intent->currency),
                ]
            ]);
        } catch (\Exception $e) {
            return redirect()->route('stripe.index')
                ->with('error', 'Error retrieving payment information: ' . $e->getMessage());
        }
    }

    /**
     * Handle cancelled payment
     */
    public function cancel()
    {
        return redirect()->route('stripe.index')
            ->with('error', 'You have cancelled the Stripe payment.');
    }

    /**
     * Handle Stripe webhooks
     */
    public function webhook(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));
        $endpoint_secret = config('stripe.webhook.secret');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                // Payment was successful
                $paymentIntent = $event->data->object;
                // You could update a transaction record here if needed
                break;
            default:
                // Unexpected event type
                return response()->json(['status' => 'Unhandled event type']);
        }

        return response()->json(['status' => 'success']);
    }
}
