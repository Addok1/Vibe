<?php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request as ValidatorRequest;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use FedaPay\FedaPay;
use FedaPay\Transaction;
use App\Http\Controllers\PaymentGatewayController;


class FedaPayController extends PaymentGatewayController
{

    protected $database;


    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function fedapay(ValidatorRequest $request)
    {

        $payment = $this->storePayment($request->all());

        $currency = "XOF";

        return view('fedapay.fedapay', compact('payment', 'currency'));
    }

    public function fedapayCheckout(ValidatorRequest $request)
    {
        $transaction_id = $request->input('transaction_id');
        
        $payment = $this->getPaymentDetail($transaction_id);

        if(!$payment){
            Log::info("Fedapay checkout Fail");
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

        if(get_payment_settings('fedapay_environment') == 'test'){
            $env = 'sandbox';
            $secret_key = get_payment_settings('fedapay_test_secret_key');
        }else{
            $env = 'live';
            $secret_key = get_payment_settings('fedapay_live_secret_key');
        }



        FedaPay::setApiKey($secret_key);
        FedaPay::setEnvironment($env); // 'sandbox' or 'live'

        $response = Transaction::create([
            "description" => "Payment For ". $transaction_id,
            "amount" => $amount,
            "callback_url" => route('fedapay.checkout.success') . '?transaction_id=' . urlencode($transaction_id),
            "currency" => [
                "iso" => $currency
            ]
        ]);

        $token = $response->generateToken();
        return redirect()->away($token->url);
        
    }

    public function fedapayCheckoutSuccess(ValidatorRequest $request)
    {

        if(get_payment_settings('fedapay_environment') == 'test'){
            $env = 'sandbox';
            $secret_key = get_payment_settings('fedapay_test_secret_key');
        }else{
            $env = 'live';
            $secret_key = get_payment_settings('fedapay_live_secret_key');
        }

        FedaPay::setApiKey($secret_key);
        FedaPay::setEnvironment($env); // 'sandbox' or 'live'

        $transaction = Transaction::retrieve($request->id,[],[]);

        if(!$transaction || $transaction->status != "approved"){
            Log::info([
                'fedapay'=> $request->all(),
                'transaction' => $transaction
            ]);
            return redirect()->route('failure');
        }


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

    public function fedapayCheckoutError(ValidatorRequest $request)
    {
        return view('failure',['failure']);

    }
}
