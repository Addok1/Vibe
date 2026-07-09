<?php

namespace App\Http\Controllers\Web;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Http\Request as ValidatorRequest;
use Carbon\Carbon;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use App\Models\Admin\Subscription;
use App\Http\Controllers\PaymentGatewayController;

class PayPalController extends PaymentGatewayController
{
    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function paypal(ValidatorRequest $request)
    {
        
        $payment = $this->storePayment($request->all());
        $currency = $payment->currency;
        if (env('APP_FOR')=='demo') {

        $currency ="USD";
        
        }


        return view('paypal.paypal',['payment' => $payment,'currency' => $currency,]);
    }

    public function payment(ValidatorRequest $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        $transaction_id = $request->input('transaction_id');
        
        $payment = $this->getPaymentDetail($transaction_id);

        if(!$payment){
            Log::info("Paypal checkout Fail");
            $requestBody = $request->all();
            Log::info($requestBody);
            return $this->respondSuccess($requestBody,'Could not find Payment');
        }elseif($payment->status == "S"){
            $request_id = null;
            return view('success',['success'],compact('web_booking_value','request_id'));
        }

        $amount = ($payment->amount);
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $request_id = $payment->request_id;
        $plan_id = $payment->plan_id;
        $currency = $payment->currency;
        
        if (env('APP_FOR')=='demo') {

        $currency ="USD";
        
        }

        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal.payment.success', [
                                'transaction_id' => $transaction_id,
                            ]),
                "cancel_url" => route('paypal.payment/cancel'),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => $currency,
                        "value" => $amount
                    ]
                ]
            ]
        ]);

        if (isset($response['id']) && $response['id'] != null) {

            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return redirect()->away($links['href']);
                }
            }

            return redirect()
                ->route('paypal.payment/cancel')
                ->with('error', 'Something went wrong.');

        } else {

            Log::info('PayPal Create Order Failed',[$response]);
            return redirect()
                ->route('paypal.payment')
                ->with('error', $response['message'] ?? 'Something went wrong.');
        }

    }

    public function paymentSuccess(ValidatorRequest $request)
    {

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request['token']);

        if (isset($response['status']) && $response['status'] == 'COMPLETED')
        {

            $web_booking_value=0;
            $payment = $this->getPaymentDetail($request->transaction_id);
            $transaction_id = $request->transaction_id;

            if(!$payment){
                Log::info("Paypal checkout Fail");
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

        } else {
            return redirect()
                ->route('paypal')
                ->with('error', $response['message'] ?? 'Something went wrong.');
        }
    }
    public function paymentCancel()
    {
        return redirect()
              ->route('paypal')
              ->with('error', $response['message'] ?? 'You have canceled the transaction.');
    }


}
