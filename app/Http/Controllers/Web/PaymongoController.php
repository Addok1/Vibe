<?php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request as ValidatorRequest;
use Carbon\Carbon;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use GuzzleHttp\Client;
use App\Http\Controllers\PaymentGatewayController;

class PaymongoController extends PaymentGatewayController
{

    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function paymongo(ValidatorRequest $request)
    {

        $payment = $this->storePayment($request->all());

        return view('paymongo.paymongo', compact('payment'));
    }

    public function paymongoCheckout(ValidatorRequest $request)
    {

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

        $amount = (float)$payment->amount;
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $request_id = $payment->request_id;
        $plan_id = $payment->plan_id;
        $currency = $payment->currency;
        
       if(get_payment_settings('paymongo_environment') == 'test'){
            $key = get_payment_settings('paymongo_test_secret_key');
       }else{
            $key = get_payment_settings('paymongo_live_secret_key');
       }


        $total1=($amount*100);

        $client = new Client();


        $attributes = [
            "send_email_receipt" => false,
            "show_description"   => false,
            "show_line_items"    => true,
            "line_items"         => [
                [
                    "currency" => $currency,
                    "amount" => $total1,
                    "name" => "Test",
                    "quantity" => 1,
                ],

            ],
            "payment_method_types" => ["card", "qrph", "billease", "dob", "dob_ubp", "brankas_bdo", "brankas_landbank", "brankas_metrobank", "gcash", "grab_pay", "paymaya"],
            'success_url' => route('checkout.success') . '?transaction_id=' . urlencode($transaction_id),
            'cancel_url'  => route('checkout.failure'),
        ];

        $response = $client->post('https://api.paymongo.com/v1/checkout_sessions', [
            'json' => [
                'data' => ['attributes' =>$attributes]
            ],
            'headers' => [
                'accept'        => 'application/json',
                'authorization' => 'Basic ' . base64_encode($key . ':'),
            ],
        ]);

        $responseBody = json_decode($response->getBody()->getContents(), true);

        if($response->getStatusCode() == 200){
            $response_url = $responseBody['data']['attributes']['checkout_url'];
            return redirect()->away($response_url);
        }else{
            Log::info("Paymongo checkout Fail Response",[
                'response' => $responseBody,
            ]);
            return redirect('failure');
        }

    }

    public function paymongoCheckoutSuccess(ValidatorRequest $request)
    {

        $web_booking_value=0;
        $payment = $this->getPaymentDetail($request->transaction_id);
        $transaction_id = $request->transaction_id;

        if(!$payment){
            Log::info("FlexPaie checkout Fail");
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

    public function paymongoCheckoutError(ValidatorRequest $request)
    {
        return view('failure',['failure']);

    }
}
