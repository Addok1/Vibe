<?php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request as ValidatorRequest;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Http\Controllers\PaymentGatewayController;

class StripeController extends PaymentGatewayController
{

    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function stripe(ValidatorRequest $request)
    {

        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;
        

        // Pass parameters to the view
        return view('stripe.stripe', compact('payment'));
    }

    public function stripeCheckout(ValidatorRequest $request)
    {

       if(get_payment_settings('stripe_environment') == 'test'){
            $key = get_payment_settings('stripe_test_secret_key');
       }else{
            $key = get_payment_settings('stripe_live_secret_key');
       }
        \Stripe\Stripe::setApiKey($key);


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

        $productname = 'taxi';
        $currency = $payment->currency;
        $total1=($payment->amount * 100);

        $session = \Stripe\Checkout\Session::create([
            'line_items'  => [
                [
                    'price_data' => [
                        'currency'     => $currency,
                        'product_data' => [
                            "name" => $productname,
                        ],
                        'unit_amount'  => $total1,
                    ],
                    'quantity'   => 1,
                ],

            ],
            'mode'        => 'payment',
            'success_url' => route('checkout.success') . '?productname=' . urlencode($productname) . '&transaction_id=' . urlencode($transaction_id),
            'cancel_url'  => route('checkout.failure'),
            'billing_address_collection' => 'required',
            'customer_creation' => 'always', 
        ]);

        return redirect()->away($session->url);
    }

    public function stripeCheckoutSuccess(ValidatorRequest $request)
    {

        $web_booking_value=0;
        $payment = $this->getPaymentDetail($request->transaction_id);
        $transaction_id = $request->transaction_id;

        if(!$payment){
            Log::info("Stripe checkout Fail");
            $requestBody = $request->all();
            Log::info($requestBody);
            return $this->respondSuccess($requestBody,'Could not find Payment');
        }elseif($payment->status == "S"){
            $request_id = null;
            return view('success',['success'],compact('web_booking_value','request_id'));
        }
        $this->payNow($transaction_id,$this->database);

        $request_id = $payment->request_id;
        $request_detail = RequestModel::where('id', $request_id)->first();

        
        $web_booking_value = $request_detail->web_booking ?? 0;



        return view('success',['success'],compact('web_booking_value','request_id'));
    }

    public function stripeCheckoutError(ValidatorRequest $request)
    {
        return view('failure',['failure']);

    }
}
