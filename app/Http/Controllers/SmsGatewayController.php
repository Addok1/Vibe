<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use App\Models\ThirdPartySetting;
use Kreait\Firebase\Contract\Database;

class SmsGatewayController  extends Controller
{
    protected $request;
    protected $database;

    public function __construct(Request $request,Database $database)
    {
        $this->request = $request;
        $this->database = $database;
    }
    public function index() 
    {
        $settings = ThirdPartySetting::where('module', 'sms')->pluck('value', 'name')->toArray();

        $settings['enable_firebase_otp'] = filter_var($settings['enable_firebase_otp'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_twilio'] = filter_var($settings['enable_twilio'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_sms_ala'] = filter_var($settings['enable_sms_ala'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_msg91'] = filter_var($settings['enable_msg91'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_sparrow'] = filter_var($settings['enable_sparrow'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_sms_india_hub'] = filter_var($settings['enable_sms_india_hub'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_kudi_sms_api_key'] = filter_var($settings['enable_kudi_sms_api_key'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_infobip'] = filter_var($settings['enable_infobip'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_termii'] = filter_var($settings['enable_termii'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_firebase_otp_control'] = filter_var($settings['enable_firebase_otp_control'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
        return Inertia::render('pages/sms_gateway/index', [
            'app_for'=>env('APP_FOR'),
            'settings' => $settings,
        ]);
    }
    
    public function update(Request $request)
    {
// dd($request->all());
    $settings = $request->only([
            'enable_firebase_otp','enable_twilio','twilio_sid','twilio_token','twilio_mobile_number','enable_sms_ala',
            'sms_ala_api_key','sms_ala_api_secret_key','sms_ala_token','sms_ala_mobile_number','enable_msg91',
            'msg91_template_id','msg91_auth_key','enable_sparrow','sparrow_sender_id','sparrow_token','enable_sms_india_hub',
            'sms_india_hub_api_key', 'sms_india_hub_sid',
            'enable_kudi_sms_api_key','kudi_sms_sender_id','kudi_sms_api_key',
            'enable_infobip','infobip_base_url','infobip_api_key','infobip_sender_id',
            'enable_termii','termii_base_url','termii_api_key','termii_sender_id','termii_channel','termii_type',
            'enable_firebase_otp_control',
        ]);


        ThirdPartySetting::where('module', 'sms')->delete(); // corrected delete command


        foreach ($settings as $key => $setting) 
        {
            // dd($setting);

            ThirdPartySetting::create(['name' => $key, 'value' => $setting, 'module' => 'sms']);                 
        }
        // firebase sync //

       
       $callFbOtp = filter_var($request->enable_firebase_otp_control, FILTER_VALIDATE_BOOLEAN);


        $this->database->getReference() ->update([
            'call_FB_OTP' => $callFbOtp
        ]);

        // dd($request->enable_firebase_otp_control, $callFbOtp);

        return response()->json(['message' => 'Sms  Destails updated successfully'], 201);

    }
}
