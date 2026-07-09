<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Request\Request as RequestModel;
use Log;
use Kreait\Firebase\Contract\Database;
use App\Http\Controllers\PaymentGatewayController;

class CashfreeController extends PaymentGatewayController
{
    public function __construct(Database $database)
    {
        $this->database = $database;
    }    

     public function create(Request $request)
     {

        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;

        $user_id = $payment->user_id;

        $user = User::find($user_id);
        return view('cashfree.cashfree', compact('payment', 'user',));
    }

      public function store(Request $request)
     {
        $web_booking_value=0;
        $payment = $this->getPaymentDetail($request->transaction_id);

        if(!$payment){
            Log::info("Cashfree checkout Fail");
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

        $transaction_id = $payment->id;

        // $amount = ($payment->amount * 100);
        $amount = round((float) $payment->amount, 2);
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $request_id = $payment->request_id;
        $plan_id = $payment->plan_id;
        $currency = $payment->currency;

        $user = User::find($user_id);


        $cashFreeEnvironment = get_payment_settings('cash_free_environment');

        $cashFreeApiKey = get_payment_settings('cash_free_production_app_id');
        $cashFreeApiSecrectKey = get_payment_settings('cash_free_production_secret_key');

        $url = "https://api.cashfree.com/pg/orders";

        if($cashFreeEnvironment=="test")
        {
            $cashFreeApiKey = get_payment_settings('cash_free_app_id');
            $cashFreeApiSecrectKey = get_payment_settings('cash_free_secret_key');
            $url = "https://sandbox.cashfree.com/pg/orders";

        }


        $headers = array(
            "Content-Type: application/json",
            "x-api-version: 2022-01-01",
            "x-client-id: ".$cashFreeApiKey,
            "x-client-secret: ".$cashFreeApiSecrectKey
        );

        $data = json_encode([
            'order_id' =>  'order_'.$transaction_id,
            'order_amount' => $amount,
            "order_currency" => $currency,
            "customer_details" => [
                "customer_id" => 'customer_'.$transaction_id,
                "customer_name" => $user->name,
                "customer_email" => $user->email,
                "customer_phone" => $user->mobile,
            ],
            "order_meta" => [
                            "return_url" => route('cashfree.success', [
                            'transaction_id' => $transaction_id,
                        ]),
            ]
        ]);

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $resp = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        $response = json_decode($resp, true);

        if(isset($response['payment_link'])){
            return redirect()->to($response['payment_link']);
        }else{
            Log::info([
                'gateway'=>'Cashfree',
                'response'=>$response,
                'error'=>$err,
            ]);
            return redirect()->route('failure');   
        }

     }
     public function success(Request $request)
     {
        $web_booking_value=0;
        $payment = $this->getPaymentDetail($request->transaction_id);
        $transaction_id = $request->transaction_id;

        if(!$payment){
            Log::info("Cashfree checkout Fail");
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
}
