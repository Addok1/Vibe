<?php
  
namespace App\Http\Controllers\Web;  
use Illuminate\Http\Request as ValidatorRequest;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use App\Http\Controllers\PaymentGatewayController;


class KhaltiController extends PaymentGatewayController
{
    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }
    
    public function khalti(ValidatorRequest $request)
    {
        $payment = $this->storePayment($request->all());
        // $publicKey = "test_public_key_caf11df7a672427b9c8f1f511912b2c0";

        $env = get_payment_settings('enable_khalti_pay');

        if($env=="test")
        {

          $publicKey =  get_payment_settings('khalti_pay_test_api_key');
        }else{
          $publicKey =  get_payment_settings('khalti_pay_live_api_key');

        }

        return view('ccavenue.khalti', compact('payment','publicKey'));
    }
 
    public function khaltiCheckoutsuccess(ValidatorRequest $request)
    {
        

        $web_booking_value=0;
        $payment = $this->getPaymentDetail($request->transaction_id);
        $transaction_id = $request->transaction_id;

        if(!$payment){
            Log::info("Khalti checkout Fail");
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


    public function paystackCheckoutError(ValidatorRequest $request)
    {
        return view('failure',['failure']);

    }
}
  