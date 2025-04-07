<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Session;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display payment page
     */
    public function index()
    {
        return view('payment.index');
    }

    /**
     * Process payment through PayPal
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('payment.success'),
                "cancel_url" => route('payment.cancel'),
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->amount
                    ]
                ]
            ]
        ]);

        if (isset($response['id']) && $response['id'] != null) {
            // Store order in session for later use
            Session::put('paypal_order_id', $response['id']);
            Session::put('payment_amount', $request->amount);

            // Redirect to PayPal approval URL
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return redirect()->away($link['href']);
                }
            }

            return redirect()->route('payment.index')
                ->with('error', 'Something went wrong with PayPal.');
        } else {
            return redirect()->route('payment.index')
                ->with('error', $response['message'] ?? 'Something went wrong with PayPal.');
        }
    }

    /**
     * Success payment callback
     */
    public function success(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        $response = $provider->capturePaymentOrder($request->token);

        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            // Payment was successful, save to database
            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'transaction_id' => $response['id'],
                'payment_method' => 'paypal',
                'amount' => Session::get('payment_amount', 0),
                'currency' => 'USD',
                'status' => $response['status'],
                'payment_data' => $response,
            ]);

            // Clear session data
            Session::forget(['paypal_order_id', 'payment_amount']);

            return view('payment.success', ['payment' => $response]);
        } else {
            return redirect()->route('payment.index')
                ->with('error', $response['message'] ?? 'Something went wrong with PayPal.');
        }
    }

    /**
     * Cancelled payment callback
     */
    public function cancel()
    {
        // Clear session data
        Session::forget(['paypal_order_id', 'payment_amount']);

        return redirect()->route('payment.index')
            ->with('error', 'You have cancelled the PayPal payment.');
    }
}
