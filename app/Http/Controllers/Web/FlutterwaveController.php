<?php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request as ValidatorRequest;
use App\Models\Admin\Subscription;
use Carbon\Carbon;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use App\Http\Controllers\PaymentGatewayController;

class FlutterwaveController extends PaymentGatewayController
{

    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function flutterwave(ValidatorRequest $request)
    {
        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;
        
        // Retrieve URL parameters
        $payment_for = $payment->payment_for;
        $user_id = $payment->user_id;
        $plan_id = $payment->plan_id;

        $env = get_payment_settings('flutter_wave_environment');

        if($env=="test")
        {

          $public_key =  get_payment_settings('flutter_wave_test_secret_key');
        }else{
          $public_key =  get_payment_settings('flutter_wave_production_secret_key');

        }
        $tx_ref = $transaction_id . time();

        if (env('APP_FOR')=='demo') {

        $currency ="NGN";
        
        }


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

        return view('flutterwave.flutterwave', compact('payment','public_key','tx_ref','user'));
    }

     public function flutterwaveCheckout(ValidatorRequest $request)
    {

        $web_booking_value=0;
        $payment = $this->getPaymentDetail($request->transaction_id);
        $transaction_id = $request->transaction_id;

        if(!$payment){
            Log::info("Flutterwave checkout Fail");
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


    public function flutterwaveCheckoutError(ValidatorRequest $request)
    {
        return view('failure',['failure']);

    }
}
