<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Admin\Subscription;
use App\Models\Request\Request as RequestModel;
use Log;
use Kreait\Firebase\Contract\Database;
use App\Http\Controllers\PaymentGatewayController;



class XenditController extends PaymentGatewayController
{

    public function xendit(Request $request)
    {

        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;
        
        // Retrieve URL parameters
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $user = User::find($user_id);
        
        $name = $user->name;
        $email = $user->email;
        $mobile = $user->mobile;


        // Pass parameters to the view
        return view('xendit.checkout', compact('payment'));


    }

    public function createInvoice(Request $request)
    {        

        $api_key =  get_payment_settings('xendi_pay_test_api_key');


        $payment = $this->getPaymentDetail($request->transaction_id);
        $transaction_id = $request->transaction_id;
        $user_id = $payment->user_id;

        if(!$payment){
            Log::info("Xendit checkout Fail");
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

        $user = User::find($user_id);
        
        $name = $user->name;
        $email = $user->email;
        $mobile = $user->mobile;

            $params = [
            'external_id' => 'xendit_' . now(),
            'payer_email' => $email,
            'description' => 'Xendit',
            'amount' => $payment->amount,
            'customer' => [
                'given_names' => $name,
                'email' => $email,
                'mobile_number' => $mobile,
            ],
            'customer_notification_preference' => [
                'invoice_created' => [
                    'whatsapp',
                    'sms',
                    'email',
                ],
                'invoice_paid' => [
                    'whatsapp',
                    'sms',
                    'email',
                ],
            ],

            'success_redirect_url' => route('xendit.callback',['amount'=>$request->amount,'user_id'=>$request->user_id,'payment_for'=>$request->payment_for,'request_id'=>$request->request_id]),
            'failure_redirect_url' => route('failure'),

        ];

        $headers = [];
        $headers[] = 'Content-Type: application/json';

        $curl = curl_init();
        $payload = json_encode($params);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_USERPWD, $api_key.":");
        curl_setopt($curl, CURLOPT_URL, 'https://api.xendit.co/v2/invoices');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_close($curl);

        $response = json_decode(curl_exec($curl));

        if(isset($response->error_code)){
            Log::info(["Xendit create Invoice Fail",$response]);
         return redirect()->route('failure');   
            
        }


        // $user = User::find($request->user_id);
        $user = User::find($payment->user_id);

        if (!$user) {
            Log::error("User not found for payment ID: ".$payment->id);
            return redirect()->route('failure');
        }

        $user->apn_token = $response->id;

        $user->save();

        
        return redirect()->to($response->invoice_url);


        
    }

    public function invoiceCallback(Request $request)
    {
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

        $user_id = $payment->user_id;

        $user = User::find($user_id);

        $api_key =  get_payment_settings('xendi_pay_test_api_key');

        $curl = curl_init();
        $headers = [];
        $headers[] = 'Content-Type: application/json';
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_USERPWD, $api_key.":");
        curl_setopt($curl, CURLOPT_URL, 'https://api.xendit.co/v2/invoices/'.$user->apn_token);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_close($curl);

        $response = json_decode(curl_exec($curl));

        if(isset($response->error_code)){
            
            return redirect()->route('failure');   
        }


        $user->apn_token = null;

        $user->save();

        if ($response->status == 'PAID') {

            $this->payNow($transaction_id,$this->database);

            $request_id = $payment->request_id;
            $request_detail = RequestModel::where('id', $request_id)->first();

            
            $web_booking_value = $request_detail->web_booking ?? 0;

            return view('success',['success'],compact('web_booking_value','request_id'));
        }

       


         return redirect()->route('failure');   

    }
}