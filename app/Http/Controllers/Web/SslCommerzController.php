<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Request as ValidatorRequest;
use App\Models\User;
use App\Models\Request\Request as RequestModel;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Database;
use App\Http\Controllers\PaymentGatewayController;
use Illuminate\Support\Facades\Http;

class SslCommerzController extends PaymentGatewayController
{
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function create(Request $request)
    {
        $payment = $this->storePayment($request->all());
        $user = User::find($payment->user_id);
        return view('sslcommerz.sslcommerz', compact('payment', 'user'));
    }

    public function initialize(Request $request)
    {
        $transaction_id = $request->input('transaction_id');
        $payment = $this->getPaymentDetail($transaction_id);

        if (!$payment) {
            return redirect()->back()->with('error', 'Payment not found.');
        }

        $user = User::find($payment->user_id);

        $amount = $payment->amount; // SSLCOMMERZ uses standard amount, not cents
        $currency = $payment->currency;

        $environment = get_payment_settings('sslcommerz_environment');
        $store_id = get_payment_settings('sslcommerz_store_id');
        $store_password = get_payment_settings('sslcommerz_store_password');

        $url = ($environment == 'production')
            ? "https://securepay.sslcommerz.com/gwprocess/v4/api.php"
            : "https://sandbox.sslcommerz.com/gwprocess/v4/api.php";

        $post_data = array();
        $post_data['store_id'] = $store_id;
        $post_data['store_passwd'] = $store_password;
        $post_data['total_amount'] = $amount;
        $post_data['currency'] = $currency;
        $post_data['tran_id'] = $transaction_id;
        $post_data['success_url'] = route('sslcommerz.success', ['transaction_id' => $transaction_id]);
        $post_data['fail_url'] = route('sslcommerz.failure', ['transaction_id' => $transaction_id]);
        $post_data['cancel_url'] = route('sslcommerz.cancel', ['transaction_id' => $transaction_id]);
        $post_data['ipn_url'] = route('sslcommerz.ipn');

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $user->name;
        $post_data['cus_email'] = $user->email;
        $post_data['cus_add1'] = 'Address Line One';
        $post_data['cus_add2'] = 'Address Line Two';
        $post_data['cus_city'] = 'City Name';
        $post_data['cus_state'] = 'State Name';
        $post_data['cus_postcode'] = '1000';
        $post_data['cus_country'] = 'Country Name';
        $post_data['cus_phone'] = $user->mobile;

        # SHIPMENT INFORMATION
        $post_data['shipping_method'] = "NO";
        $post_data['num_of_item'] = "1";
        $post_data['product_name'] = "Transport Service";
        $post_data['product_category'] = "Service";
        $post_data['product_profile'] = "general";


        $response = Http::asForm()->post($url, $post_data);

        $sslcz = $response->json();

        if (isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL'] != "") {
            return redirect($sslcz['GatewayPageURL']);
        }
        else {
            Log::error("SSLCommerz Init Failed: " . json_encode($sslcz));
            return redirect()->back()->with('error', 'Payment initialization failed.');
        }
    }

    public function success(Request $request)
    {
        Log::info($request->all());
        $transaction_id = $request->input('transaction_id');
        // Note: SSLCommerz post data also contains tran_id, but we passed it as query param too.

        if (!$transaction_id) {
            $transaction_id = $request->input('tran_id');
        }

        $payment = $this->getPaymentDetail($transaction_id);

        if (!$payment) {
            return $this->respondSuccess($request->all(), 'Could not find Payment');
        }

        // Validate Payment via Validation API
        $val_id = $request->input('val_id');
        if ($this->validatePayment([
            'val_id' => $request->input('val_id'),
            'amount' => $payment->amount,
            'currency' => $payment->currency
        ])) {
            // $this->payNow($transaction_id, $this->database);
            if ($payment->status !== 'S') {
                $this->payNow($transaction_id, $this->database);
            }

            $request_id = $payment->request_id;
            $web_booking_value = 0;

            if ($request_id) {
                $request_detail = RequestModel::where('id', $request_id)->first();
                $web_booking_value = $request_detail->web_booking ?? 0;
            }

            return view('success', ['success'], compact('web_booking_value', 'request_id'));
        }
        else {
            return view('failure', ['failure']);
        }

    }

    public function failure(Request $request)
    {
        return view('failure', ['failure']);
    }

    public function cancel(Request $request)
    {
        return view('failure', ['failure']);
    }

    public function ipn(Request $request)
    {
        // IPN handling logic
        // This is called by SSLCOMMERZ in background
        Log::info("SSLCOMMERZ IPN:", $request->all());

        $tran_id = $request->input('tran_id');
        $val_id = $request->input('val_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');

        if ($tran_id && $val_id) {
            $payment = $this->getPaymentDetail($tran_id);
            if ($payment && $payment->status !== 'S') {
                if ($this->validatePayment([
                    'val_id' => $val_id,
                    'amount' => $amount,
                    'currency' => $currency
                ])) {
                    $this->payNow($tran_id, $this->database);
                }
            }
        }
    }

    // protected function validatePayment($request)
    // {

    //     $val_id = $request['val_id'] ?? null;
    //     $amount = $request['amount'] ?? null;
    //     $currency = $request['currency'] ?? null;
    //     $post_data = $request['post_data'] ?? null;
    // // $amount   = $request->amount;
    // // $currency = $request->currency;
    // // $post_data = $request->all();
    //     if (empty($val_id)) {
    //         return false;
    //     }

    //     $store_id = get_payment_settings('sslcommerz_store_id');
    //     $store_password = get_payment_settings('sslcommerz_store_password');
    //     $environment = get_payment_settings('sslcommerz_environment');

    //     $requested_url = ($environment == 'production')
    //         ? "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php"
    //         : "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php";

    //     $handle = curl_init();
    //     curl_setopt($handle, CURLOPT_URL, $requested_url . "?val_id=" . $val_id . "&store_id=" . $store_id . "&store_passwd=" . $store_password . "&v=1&format=json");
    //     curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false); # FIXED SECURITY ISSUE
    //     curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); # FIXED SECURITY ISSUE

    //     $result = curl_exec($handle);
    //     $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    //     if ($code == 200 && !(curl_errno($handle))) {
    //         $result = json_decode($result, true);
    //         $status = $result['status'];
    //         $validated_amount = $result['amount'];
    //         $validated_currency = $result['currency'];

    //         if ($status == 'VALID' || $status == 'VALIDATED') {
    //             if ($currency == $validated_currency && (float)$amount == (float)$validated_amount) {
    //                 return true;
    //             }
    //         }
    //     }

    //     return false;
    // }

    protected function validatePayment($request)
    {
        $val_id = $request['val_id'] ?? null;
        $amount = $request['amount'] ?? null;
        $currency = $request['currency'] ?? null;
    
        if (empty($val_id)) {
            return false;
        }
    
        $store_id = get_payment_settings('sslcommerz_store_id');
        $store_password = get_payment_settings('sslcommerz_store_password');
        $environment = get_payment_settings('sslcommerz_environment');
    
        $requested_url = ($environment == 'production')
            ? "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php"
            : "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php";
    
        $url = $requested_url . "?val_id=" . $val_id .
            "&store_id=" . $store_id .
            "&store_passwd=" . $store_password .
            "&v=1&format=json";
    
        $response = Http::get($url);
    
        if (!$response->successful()) {
            return false;
        }
    
        $result = $response->json();
    
        if (!isset($result['status'])) {
            return false;
        }
    
        if ($result['status'] == 'VALID' || $result['status'] == 'VALIDATED') {
    
            if (
                $result['currency'] == $currency &&
                (float)$result['amount'] == (float)$amount
            ) {
                return true;
            }
        }
    
        return false;
    }

}