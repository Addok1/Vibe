<?php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request as ValidatorRequest;
use App\Models\Admin\Subscription;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use App\Http\Controllers\PaymentGatewayController;

class PaystackController extends PaymentGatewayController
{

    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function paystack(ValidatorRequest $request)
    {

// Log::info("-----Paystack-checkout-tamil");
// Log::info($request->all());
        // Retrieve URL parameters
        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;
        
        // Retrieve URL parameters
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $plan_id = $payment->plan_id;
        $user = User::find($user_id);

        $email = $user->email ?? "test@test.com";
        if (env('APP_FOR')=='demo') {

        $currency_code ="NGN";
        
        }

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
        // $key = "pk_test_527da4a4be4324509fbd32906d03d826eefdb395";
        $env = get_payment_settings('paystack_environment');
        $key = get_payment_settings('paystack_production_secret_key');

        if($env=="test"){

          $key = get_payment_settings('paystack_test_secret_key');

        }

        return view('paystack.paystack', compact('user', 'payment','key','email'));
    }

    public function paystackCheckout(ValidatorRequest $request)
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
