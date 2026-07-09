<?php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request as ValidatorRequest;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\PaymentGatewayController;

class MyFatooraController extends PaymentGatewayController
{

    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function myfatoora(ValidatorRequest $request)
    {
// dd(config('myfatoora.pk'));

        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;

        // Retrieve URL parameters
        $amount = (float)($payment->amount);
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $request_id = $payment->request_id;
        $plan_id = $payment->plan_id;
        $currency = $payment->currency;

        $user = User::find($user_id);


        $env = get_payment_settings('myfatoora_environment');
        if($env == 'test'){
            $base_url = 'https://apitest.myfatoorah.com/v2/';
            $token = get_payment_settings('myfatoora_test_token');
        }else{
            if ($currency == 'SAU') {
                $base_url = 'https://api-sa.myfatoorah.com/v2/';
            } elseif ($currency == 'QAT') {
                $base_url = 'https://api-qa.myfatoorah.com/v2/';
            } else {
                $base_url = 'https://api.myfatoorah.com/v2/';
            }
            $token = get_payment_settings('myfatoora_live_token');
        }


        $initiatePaymentResponse = Http::withToken($token)->post($base_url . 'InitiatePayment', [
            'InvoiceAmount' => $amount,
            'CurrencyIso' => $currency, // You can change this based on your logic
        ]);

        if ($initiatePaymentResponse->successful()) {
            $payment_methods = $initiatePaymentResponse['Data']['PaymentMethods'];
        } else {
            $payment_methods = [];
        }

        $response = Http::withToken($token)->post($base_url . 'InitiateSession', [
            'CustomerIdentifier' => $transaction_id,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $session_id = $data['Data']['SessionId'];
            $payment_data = [
                'country_code' => $data['Data']['CountryCode'],
                'session_id' => $data['Data']['SessionId'],
                'mode'=>$env,
            ];
            return view('myfatoora.myfatoora', compact('payment','payment_data','payment_methods'));
        }

        Log::info([
            'gateway' => 'myFatoora',
            'initiate'=>[
                'status'  => $initiatePaymentResponse->status(),
                'ok'      => $initiatePaymentResponse->ok(),
                'success' => $initiatePaymentResponse->successful(),
                'failed'  => $initiatePaymentResponse->failed(),
                'headers' => $initiatePaymentResponse->headers(),
                'body'    => $initiatePaymentResponse->body(),
                'json'    => $initiatePaymentResponse->json(),
            ],
            'payment'=>[
                'status'  => $response->status(),
                'ok'      => $response->ok(),
                'success' => $response->successful(),
                'failed'  => $response->failed(),
                'headers' => $response->headers(),
                'body'    => $response->body(),
                'json'    => $response->json(),
            ],
        ]);
        return redirect()->route('failure');
    }

    public function myfatooraCheckout(ValidatorRequest $request)
    {

        $PaymentMethodId=($request->input('payment_method_id') );
        $sessionId=($request->input('session_id') );
        
        $transaction_id = $request->input('transaction_id');
        
        $payment = $this->getPaymentDetail($transaction_id);

        if(!$payment){
            Log::info("Flexpaie checkout Fail");
            $requestBody = $request->all();
            Log::info($requestBody);
            return $this->respondSuccess($requestBody,'Could not find Payment');
        }elseif($payment->status == "S"){

            $request_id = $payment->request_id;
            $request_detail = RequestModel::where('id', $request_id)->first();
            $web_booking_value = $request_detail->web_booking ?? 0;

            return view('success',['success'],compact('web_booking_value','request_id'));
        }elseif($payment->status == "F"){
            return view('failure',['failure']);
        }

        $amount = ($payment->amount);
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $request_id = $payment->request_id;
        $plan_id = $payment->plan_id;
        $currency = $payment->currency;
        
        $user = User::find($user_id);

        $env = get_payment_settings('myfatoora_environment');
        if($env == 'test'){
            $base_url = 'https://apitest.myfatoorah.com/v2/';
            $token = get_payment_settings('myfatoora_test_token');
        }else{
            if ($currency == 'SAU') {
                $base_url = 'https://api-sa.myfatoorah.com/v2/';
            } elseif ($currency == 'QAT') {
                $base_url = 'https://api-qa.myfatoorah.com/v2/';
            } else {
                $base_url = 'https://api.myfatoorah.com/v2/';
            }
            $token = get_payment_settings('myfatoora_live_token');
        }
        

        $body = [
            "PaymentMethodId" => $PaymentMethodId,
            "SessionId" => $sessionId,
            "InvoiceValue" => round($amount, 3),
            "CustomerName" => $user->name,
            "CustomerAddress"=>[
                "Block"=>"Test",
                "Street"=>"Test",
                "HouseBuildingNo"=>"Test",
                "AddressInstructions"=>"Test"
            ],
            "InvoiceItems"=>[
                (object)[
                    "ItemName"=>"Ride",
                    "Quantity"=>1,
                    "UnitPrice"=>$amount,
                ]
            ],
            "DisplayCurrencyIso" => $env == 'test' ? 'KWD' : ($currency ?? 'KWD'),
            "CallBackUrl" => route('myfatoora.checkout.success') . '?transaction_id=' . urlencode($transaction_id),
            "ErrorUrl" => route('failure'),
            "Language" => "ar",
            "CustomerReference" => "noshipping-nosupplier"
        ];
        

        $response = Http::withToken($token)->post($base_url . 'ExecutePayment', $body);
        if ($response->successful()) {
            session()->put('transaction_reference', $response->json()['Data']['InvoiceId']);
            return response()->json($response->json()['Data']['PaymentURL'], $response->status());
        } else {
        Log::info([
            'gateway' => 'myFatoora',
            'checkout'=>[
                'status'  => $response->status(),
                'ok'      => $response->ok(),
                'success' => $response->successful(),
                'failed'  => $response->failed(),
                'headers' => $response->headers(),
                'body'    => $response->body(),
                'json'    => $response->json(),
            ],
        ]);
        return redirect()->route('failure');
            return redirect()->route('failure');
        }

    }

    public function myfatooraCheckoutSuccess(ValidatorRequest $request)
    {

        $web_booking_value=0;
        $payment = $this->getPaymentDetail($request->transaction_id);
        $transaction_id = $request->transaction_id;

        if(!$payment){
            Log::info("MyFatoorah checkout Fail");
            $requestBody = $request->all();
            Log::info($requestBody);
            return $this->respondSuccess($requestBody,'Could not find Payment');
        }elseif($payment->status == "S"){

            $request_id = $payment->request_id;
            $request_detail = RequestModel::where('id', $request_id)->first();
            $web_booking_value = $request_detail->web_booking ?? 0;

            return view('success',['success'],compact('web_booking_value','request_id'));
        }elseif($payment->status == "F"){
            return view('failure',['failure']);
        }
        $this->payNow($transaction_id,$this->database);

        $request_id = $payment->request_id;
        $request_detail = RequestModel::where('id', $request_id)->first();

        
        $web_booking_value = $request_detail->web_booking ?? 0;

        return view('success',['success'],compact('web_booking_value','request_id'));
    }

    public function myfatooraCheckoutError(ValidatorRequest $request)
    {
        return view('failure',['failure']);

    }
}
