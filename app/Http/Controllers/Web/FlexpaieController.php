<?php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request as ValidatorRequest;
use Illuminate\Support\Facades\Http;
use App\Models\Admin\Subscription;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use App\Http\Controllers\PaymentGatewayController;

class FlexpaieController extends PaymentGatewayController
{

    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function flexpaie(ValidatorRequest $request)
    {

        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;
        
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $plan_id = $payment->plan_id;


        $user = User::find($user_id);

        if($payment_for == 'subscription'){
            $driver = $user->driver;
            if(!$driver){
                $this->throwAuthorizationException();
            }

            if($driver->is_subscribed){
                $this->throwCustomException('Driver already subscribed');
            }

            $vehicle_types = $driver->driverVehicleTypeDetail->pluck('vehicle_type');

            $plan = Subscription::active()->where('id',$plan_id)->whereIn('vehicle_type_id',$vehicle_types)->first();

            if(!$plan){
                $this->throwCustomException('Subscription is not Valid or Incorrect');
            }
        }

        return view('flexpaie.flexpaie', compact('payment'));
    }

    public function flexpaieCheckout(ValidatorRequest $request)
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

        $amount = ($payment->amount);
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $request_id = $payment->request_id;
        $plan_id = $payment->plan_id;
        $currency = $payment->currency;
        
        $env = get_payment_settings('flexpaie_environment');

        $token = get_payment_settings('flexpaie_production_bearer_token');

        if(get_payment_settings('flexpaie_environment') == 'test') {       
            $token = get_payment_settings('flexpaie_test_bearer_token');
        }

        $user = User::find($user_id);

        $mobile = ltrim($user->mobile_number, '+');


        $payload = [
            'merchant'    => 'SAFARI_CUDDLE',
            'type'        => '1',
            'phone'       => $mobile,
            'reference'   => 'TXN_' . $transaction_id, // or you can use $request_id if needed
            'amount'      => $amount,
            'currency'    => $currency,
            'callbackUrl' => 'https://safari.cuddle-soft.com/payment-callback?transaction_id=' . $transaction_id ,
        ];

        $response = Http::withToken($token)
            ->post('https://backend.flexpay.cd/api/rest/v1/paymentService', $payload);


        if($response->status() ==200){
            $json_data = $response->json();
            if($json_data['code'] == 1){
                return response()->json([
                    'status' => 500,
                    'body' => $response->json(),
                ],500);
            }else{
                return response()->json([
                    'status' => $response->status(),
                    'body' => $response->json(),
                ],200);
            }
        }
        return response()->json([
            'status' => $response->status(),
            'body' => $response->json(),
        ],500);
    }

    public function flexpaieCheckoutSuccess(ValidatorRequest $request)
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

}
