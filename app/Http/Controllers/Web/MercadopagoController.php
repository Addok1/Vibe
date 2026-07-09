<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Admin\Subscription;
use Kreait\Firebase\Contract\Database;
use Carbon\Carbon;
use App\Models\Request\Request as RequestModel;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\PaymentGatewayController;


class MercadopagoController extends PaymentGatewayController
{
    protected $database;


    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function mercadepago(Request $request)
    {


        $environment =  get_payment_settings('mercadopago_environment');

        if ($environment=="test") {
            $public_key =  get_payment_settings('mercadopago_test_public_key');
            $token =  get_payment_settings('mercadopago_test_access_token');
        }else{
            $public_key =  get_payment_settings('mercadopago_live_public_key');
            $token =  get_payment_settings('mercadopago_live_access_token');
        }

        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;

        $user_id = $payment->user_id;

        $payment_for = $payment->payment_for;
        $user = User::find($user_id);

        $current_timestamp = Carbon::now()->timestamp;
        $description = $current_timestamp.'----'.$transaction_id;

        $amount = $payment->amount;
        $currency = $payment->currency;

        try {
            MercadoPagoConfig::setAccessToken($token);

            $client = new PreferenceClient();

            $back_urls = [
                "success" => (string)route('mercadopago.success'),
                "failure" => (string)env('APP_URL').'/failure',
                "pending" => (string)env('APP_URL').'/pending'
            ];
            
            $preference = $client->create([
                "items" => [
                    [
                        "title" => $payment_for,
                        "description" => $description,
                        "quantity" => 1,
                        "currency_id"=>$currency,
                        "unit_price" => (float) $amount
                    ]
                ],
                "external_reference"=>$description,
                "payer"=>[
                    'name'=>$user->name ?? "test",
                    'email'=>$user->email ?? "test@test.com",
                ],
                "back_urls" =>$back_urls,
            ]);


        } catch (MPApiException $e) {
            $error = $e->getApiResponse()->getContent();
            return $this->throwCustomException('Mercado Pago API Error: ' . $error['message']);
        } catch (\Exception $e) {
            return $this->throwCustomException('An unexpected error occurred.: '.$e->getMessage());
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

            $plan_id = $payment->plan_id;

            $plan = Subscription::active()->where('id',$plan_id)->whereIn('vehicle_type_id',$vehicle_types)->first();

            if(!$plan){
                $this->throwCustomException('Subscription is not Valid or Incorrect');
            }
        }

        // Ensure that the order ID is passed correctly to the view
        return view('mercadopago.checkout', compact('public_key', 'payment','preference'))->render();
    }



public function mercadopagoWebhook(Request $request)
{
    \Log::info('Webhook Method:', [$request->method()]);
    \Log::info('Webhook Headers:', $request->headers->all());
    \Log::info('Raw Body:', [$request->getContent()]);

    $data = json_decode($request->getContent(), true);
    \Log::info('Parsed JSON:', $data);

    return response()->json(['success' => true]);
}




    public function mercadopagoCheckout(Request $request){


        $exploded_reference = explode('----', $request->external_reference);
        $transaction_id = $exploded_reference[1];
        $web_booking_value=0;
        $payment = $this->getPaymentDetail($transaction_id);

        if(!$payment){
            Log::info("Mercadopago checkout Fail");
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
