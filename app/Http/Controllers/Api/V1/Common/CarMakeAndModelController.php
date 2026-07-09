<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\V1\BaseController;
use Carbon\Carbon;
use Sk\Geohash\Geohash;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Database;
use App\Helpers\Rides\FetchDriversFromFirebaseHelpers;
use App\Models\Admin\DriverAvailability;
use App\Models\Master\MobileAppSetting;
use App\Transformers\Requests\MobileAppSettingsTransformer;
use Illuminate\Support\Facades\DB;
use Config;
use App\Models\ThirdPartySetting;

/**
 * @group Vehicle Management
 *
 * APIs for vehilce management apis. i.e types,car makes,models apis
 */
class CarMakeAndModelController extends BaseController
{
    use FetchDriversFromFirebaseHelpers;

    /**
     * The country model instance.
     *
     * @var \Kreait\Firebase\Contract\Database
     */
    protected $database;

    /**
     * CancellationReasonsController constructor.
     *
     * @param \Kreait\Firebase\Contract\Database $database;
     */
    public function __construct(Database $database)
    {
        $this->database = $database;

    }


    /**
    * Get App Modules
    * @response 
    * {
    *      "success": true,
    *      "message": "success",
    *      "enable_owner_login": "1",
    *      "enable_email_otp": "1",
    *      "enable_user_referral_earnings": null,
    *      "enable_driver_referral_earnings": null,
    *      "firebase_otp_enabled": false
    *      "enable_email_login": "1",
    * }
    */
    public function getAppModule()
    {
        $get_third_party_settings = ThirdPartySetting::whereIn('name', [
            'enable_google_social_login',
            'enable_facebook_social_login',
            'enable_apple_social_login',
            'google_client_id',
            'google_client_secret',
            'facebook_client_id',
            'facebook_client_secret',
            'apple_client_id',
        ])->pluck('value', 'name');

        $enable_owner_login =  get_settings('shoW_owner_module_feature_on_mobile_app');

        $enable_email_otp =  get_settings('shoW_email_otp_feature_on_mobile_app');
     
        $firebase_otp_enabled =  get_sms_settings('enable_firebase_otp');

        $firebase_otp_enabled =  get_sms_settings('enable_firebase_otp');
        // referral
        $enable_user_referral_earnings =  get_settings('enable_user_referral_earnings');

        $enable_driver_referral_earnings =  get_settings('enable_driver_referral_earnings');

        // email login
        $enable_email_login =  get_settings('enable_email_login');
        // user sign-in
        $enable_user_sign_in_email_otp = get_settings('enable_user_sign_in_email_otp');
        $enable_user_sign_in_email_password = get_settings('enable_user_sign_in_email_password');
        $enable_user_sign_in_mobile_otp = get_settings('enable_user_sign_in_mobile_otp');
        $enable_user_sign_in_mobile_password = get_settings('enable_user_sign_in_mobile_password');
        //user sign-up
        // $enable_user_sign_up_email_otp = get_settings('enable_user_sign_up_email_otp');
        // $enable_user_sign_up_email_password = get_settings('enable_user_sign_up_email_password');
        // $enable_user_sign_up_mobile_otp = get_settings('enable_user_sign_up_mobile_otp');
        // $enable_user_sign_up_mobile_password = get_settings('enable_user_sign_up_mobile_password');
        //driver sign-in
        $enable_driver_sign_in_email_otp = get_settings('enable_driver_sign_in_email_otp');
        $enable_driver_sign_in_email_password = get_settings('enable_driver_sign_in_email_password');
        $enable_driver_sign_in_mobile_otp = get_settings('enable_driver_sign_in_mobile_otp');
        $enable_driver_sign_in_mobile_password = get_settings('enable_driver_sign_in_mobile_password');
        // owner sign-in //
        $enable_owner_sign_in_email_otp = get_settings('enable_owner_sign_in_email_otp');
        $enable_owner_sign_in_email_password = get_settings('enable_owner_sign_in_email_password');
        $enable_owner_sign_in_mobile_otp = get_settings('enable_owner_sign_in_mobile_otp');
        $enable_owner_sign_in_mobile_password = get_settings('enable_owner_sign_in_mobile_password');

        $enable_user_email_login = get_settings('enable_user_email_login');
        $enable_user_mobile_login = get_settings('enable_user_mobile_login');
        $enable_driver_email_login = get_settings('enable_driver_email_login');
        $enable_driver_mobile_login = get_settings('enable_driver_mobile_login');
        $enable_owner_email_login = get_settings('enable_owner_email_login');
        $enable_owner_mobile_login = get_settings('enable_owner_mobile_login');
        $enable_driver_sign_up_feature = get_settings('enable_driver_sign_up_feature');
        $enable_user_document_upload = get_settings('enable_user_document_upload');
        
        $enable_google_social_login = $get_third_party_settings['enable_google_social_login'] ?? 0;

        $enable_facebook_social_login = $get_third_party_settings['enable_facebook_social_login'] ?? 0;

        $enable_apple_social_login = $get_third_party_settings['enable_apple_social_login'] ?? 0;

        $google_client_id = $get_third_party_settings['google_client_id'] ?? null;

        $google_client_secret = $get_third_party_settings['google_client_secret'] ?? null;

        $facebook_client_id = $get_third_party_settings['facebook_client_id'] ?? null;

        $facebook_client_secret = $get_third_party_settings['facebook_client_secret'] ?? null;

        $apple_client_id = $get_third_party_settings['apple_client_id'] ?? null;


        $firebase_otp = false;

        if($firebase_otp_enabled==1)
        {
            $firebase_otp = true;
        }

        return response()->json(['success'=>true,"message"=>'success','enable_owner_login'=>$enable_owner_login,
                                'enable_email_otp'=>$enable_email_otp,
                                'enable_user_referral_earnings'=>$enable_user_referral_earnings,
                                'enable_driver_referral_earnings'=>$enable_driver_referral_earnings,
                                'firebase_otp_enabled'=>$firebase_otp,
                                'enable_email_login'=>$enable_email_login,
                                'enable_user_sign_in_email_otp'=>$enable_user_sign_in_email_otp,
                                'enable_user_sign_in_email_password'=>$enable_user_sign_in_email_password,
                                'enable_user_sign_in_mobile_otp'=>$enable_user_sign_in_mobile_otp,
                                'enable_user_sign_in_mobile_password'=>$enable_user_sign_in_mobile_password,
                                // 'enable_user_sign_up_email_otp'=>$enable_user_sign_up_email_otp,
                                // 'enable_user_sign_up_email_password'=>$enable_user_sign_up_email_password,
                                // 'enable_user_sign_up_mobile_otp'=>$enable_user_sign_up_mobile_otp,
                                // 'enable_user_sign_up_mobile_password'=>$enable_user_sign_up_mobile_password,
                                'enable_driver_sign_in_email_otp'=>$enable_driver_sign_in_email_otp,
                                'enable_driver_sign_in_email_password'=>$enable_driver_sign_in_email_password,
                                'enable_driver_sign_in_mobile_otp'=>$enable_driver_sign_in_mobile_otp,
                                'enable_driver_sign_in_mobile_password'=>$enable_driver_sign_in_mobile_password,
                                'enable_owner_sign_in_email_otp'=>$enable_owner_sign_in_email_otp,
                                'enable_owner_sign_in_email_password'=>$enable_owner_sign_in_email_password,
                                'enable_owner_sign_in_mobile_otp'=>$enable_owner_sign_in_mobile_otp,
                                'enable_owner_sign_in_mobile_password'=>$enable_owner_sign_in_mobile_password,
                                'enable_user_email_login' =>$enable_user_email_login,
                                'enable_user_mobile_login' =>$enable_user_mobile_login,
                                'enable_driver_email_login' =>$enable_driver_email_login,
                                'enable_driver_mobile_login' =>$enable_driver_mobile_login,
                                'enable_owner_email_login' =>$enable_owner_email_login,
                                'enable_owner_mobile_login' =>$enable_owner_mobile_login,
                                'enable_driver_sign_up_feature' => $enable_driver_sign_up_feature,
                                'enable_user_document_upload' => $enable_user_document_upload,
                                'enable_google_social_login' => $enable_google_social_login,
                                'enable_facebook_social_login' => $enable_facebook_social_login,
                                'enable_apple_social_login' => $enable_apple_social_login,
                                'google_client_id' =>  $google_client_id,
                                'google_client_secret' => $google_client_secret,
                                'facebook_client_id' =>  $facebook_client_id,
                                'facebook_client_secret' => $facebook_client_secret,
                                'apple_client_id' => $apple_client_id,
                               

                            ]);
    }


    public function mobileAppMenu()
    {
        $ride_modules = MobileAppSetting::whereActive(true)->orderBy('order_by', 'asc');

        $app_modules = filter($ride_modules, new MobileAppSettingsTransformer)->get();

        return $this->respondSuccess($app_modules, 'ride_modules_listed');

    }
    /**
     * Test Api
     * @hideFromAPIDocumentation
     * 
     * */
    public function testApi()
    {
        $notificationData = [
            'id' => uniqid(),  // Generate a unique ID for the notification
            'body' => "New User Registered",  // Notification body text
            'title' => "New User Registered", // Notification title
            'read' => false,  // Default to unread
            'updated_at' => round(microtime(true) * 1000), // Unix timestamp in milliseconds
            'url' => "http://127.0.0.1:8000/admins" // URL to redirect when clicked
        ];
        
        // Insert data into Firebase's 'admin-notification' node with the unique ID
        $this->database->getReference('admin-notification/' . $notificationData['id'])
                       ->set($notificationData);
    
        return response()->json(['message' => 'Notification created successfully'], 201);
    }
    
}
