<?php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request as ValidatorRequest;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use App\Http\Controllers\PaymentGatewayController;

class RazorPayController extends PaymentGatewayController
{
    protected $api;
    protected $database;

    public function __construct(Database $database)
    {
        $environment =  get_payment_settings('razor_pay_environment');

        if ($environment=="test") {
            $keySecret =  get_payment_settings('razor_pay_test_secrect_key');
            $keyId =  get_payment_settings('razor_pay_test_api_key');
        }else{
            $keySecret =  get_payment_settings('razor_pay_secrect_key');
            $keyId =  get_payment_settings('razor_pay_live_api_key');
        }
// dd($keyId);
        $this->database = $database;

        $this->api = new Api($keyId, $keySecret);
    }

    public function razorpay(Request $request)
    {
        // Log::info("test api v1");
        // Log::info($request->all());

    //  $keyId = "rzp_test_b444CSYRGAtdnV";  // Replace with your test key ID
    //  $keySecret = "Q8ABpY18WPsyJ8LGvqGZR70l"; // Replace with your test key secret

        $environment =  get_payment_settings('razor_pay_environment');
     
        if ($environment=="test") {
            $keySecret =  get_payment_settings('razor_pay_test_secrect_key');
            $keyId =  get_payment_settings('razor_pay_test_api_key');
        }else{
            $keySecret =  get_payment_settings('razor_pay_secrect_key');
            $keyId =  get_payment_settings('razor_pay_live_api_key');
        }
        // dd($environment,$keyId);

        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;
// dd($this->api->order);

        // Extract parameters from the request
        $name = $user->name ?? 'bala';
        $email = $user->email ?? 'balathemask@gmail.com';
        $mobile = $user->mobile ?? '9790200663';

        $amount = ( $payment->amount * 100);
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $request_id = $payment->request_id;
        $plan_id = $payment->plan_id;
        $currency = $payment->currency;

        $user = User::find($user_id);

        // Create an order
        $order = $this->api->order->create([
            'amount' => $amount, // amount in paisa
            'currency' => $currency,
            'receipt' => 'order_' . $transaction_id,
            'payment_capture' => 1 // auto capture
        ]);

        // Ensure that the order ID is passed correctly to the view
        return view('Razorpay.razorpay', ['order' => $order, 'key' => $keyId, 'payment' => $payment,  'user' => $user,]);
    }


    public function razorpay_success(ValidatorRequest $request)
    {
// Log::info("razor pay sucess from api/v1");

// Log::info(request()->all());
// dd(request()->all());

        $web_booking_value=0;
        $payment = $this->getPaymentDetail($request->transaction_id);
        $transaction_id = $request->transaction_id;

        if(!$payment){
            Log::info("Razorpay checkout Fail");
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
