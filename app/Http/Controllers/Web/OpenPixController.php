<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Base\Constants\Masters\WalletRemarks;
use App\Models\Payment\UserWallet;
use App\Models\Payment\DriverWallet;
use App\Models\Payment\OwnerWallet;
use App\Models\Payment\OwnerWalletHistory;
use App\Models\Payment\UserWalletHistory;
use App\Models\Payment\DriverWalletHistory;
use App\Models\Admin\Subscription;
use App\Models\Admin\SubscriptionDetail;
use App\Base\Constants\Masters\PushEnums;
use App\Jobs\Notifications\SendPushNotification;
use Kreait\Firebase\Contract\Database;
use App\Base\Constants\Auth\Role;
use Carbon\Carbon;
use App\Models\Request\Request as RequestModel;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Resources\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\PaymentGatewayController;

class OpenPixController extends PaymentGatewayController
{
    protected $database;


    public function __construct(Database $database)
    {
        $this->database = $database;
    }

   
    public function openPix(Request $request)
    {
        $environment = get_payment_settings('openpix_environment'); // 'test' or 'live'

        if ($environment === "test") {
            $apiKey = get_payment_settings('openpix_test_api_key');
        } else {
            $apiKey = get_payment_settings('openpix_live_api_key');
        }
        $payment = $this->storePayment($request->all());
        $transaction_id = $payment->id;

        $user = User::find($payment->user_id);
        $plan_id = $payment->plan_id;
        $payment_for = $payment->payment_for;
        $amount = (float) $payment->amount;
        $currency = "BRL";

        $payer = [
            "name" => $user->name ?? 'Guest',
            "email" => $user->email ?? 'guest@example.com',
        ];

        $description = now()->timestamp . "----" . $payment->id;

        $correlationID = $payment->id;

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.openpix.com.br/api/v1/charge', [
                'correlationID' => $correlationID,
                'value' => intval($amount * 100), // in centavos
                'comment' => $description,
                'customer' => $payer,
            ]);

            $data = $response->json();

            if (isset($data['charge']['qrCodeImage']) && isset($data['charge']['brCode'])) {
                return view('openpix.pix', [
                    'payment' => $data['charge'],
                    'amount' => $amount,
                    'currency' => $currency,
                ]);
            }

            return $this->throwCustomException('Failed to create OpenPix charge. ' . json_encode($data));
        } catch (\Exception $e) {
            return $this->throwCustomException('OpenPix Error: ' . $e->getMessage());
        }
    }


    public function webhook(Request $request)
    {
        Log::info($request->all());

        if(count($request->all()) == 4 && $request->input('authorization') == null){
            return response()->json(['success' => true]);
        }

        $exploded_reference = explode('----', $request->charge['comment']);

        \Log::info($exploded_reference);

        $web_booking_value = 0;
        $transaction_id = $exploded_reference[1];

        $payment = $this->getPaymentDetail($transaction_id);

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


        return response()->json(['success' => true]);

    }



   
}
