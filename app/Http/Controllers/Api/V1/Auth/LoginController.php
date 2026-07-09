<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Base\Constants\Auth\Role;
use App\Http\Requests\Auth\SendLoginOTPRequest;
use App\Http\Requests\Auth\App\GenericAppLoginRequest;
use App\Http\Controllers\Web\Auth\LoginController as BaseLoginController;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Models\MobileOtp;
use App\Http\Requests\Auth\Registration\ValidateMobileOTPRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\V1\Driver\OnlineOfflineController;
use Kreait\Firebase\Contract\Database;
use App\Services\Auth\SocialIdentityService;

class LoginController extends BaseLoginController
{
    /**
     * Login user and respond with access token and refresh token.
     * @group User-Login
     *
     * @param \App\Http\Requests\Auth\App\GenericAppLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @bodyParam email string optional email of the user entered
     * @bodyParam mobile string optional mobile of the user entered
     * @bodyParam password string optional password of the user entered
     * @bodyParam device_token string required fcm_token of the user entered

     * @response
     * {
     *     "success": true,
     *     "message": "success",
     *     "access_token": "98|6jzNOIahjd2V72je0OeucPRuaRiIhJxWKXFvNVUr7027d348"
     * }
     */
    public function loginUser(GenericAppLoginRequest $request)
    {

        return $this->loginUserAccountApp($request, Role::USER);
    }

    /**
     * Login driver and respond with access token and refresh token.
     * @group User-Login
     *
     * @param \App\Http\Requests\Auth\App\GenericAppLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
      * @bodyParam email string optional email of the user entered
     * @bodyParam mobile string optional mobile of the user entered
     * @bodyParam social_unique_id string optional mobile of the user entered
     * @bodyParam password string optional password of the user entered
     * @bodyParam device_token string optional fcm_token for push notification
     * @bodyParam apn_token string optional fcm_token for ios push notification
     * @bodyParam login_by string required i.e android,ios

     * @response 
     * {
     *     "success": true,
     *     "message": "success",
     *     "access_token": "98|6jzNOIahjd2V72je0OeucPRuaRiIhJxWKXFvNVUr7027d348"
     * }
    */
    public function loginDriver(GenericAppLoginRequest $request)
    {
        // $request->validate([
        //     'mobile_n' => 'required|decimal'
        //     ]);
    
        //     dd("m");

        if($request->has('role') && $request->role=='driver'){
            return $this->loginUserAccountApp($request, Role::DRIVER);
        }

        if($request->has('role') && $request->role=='owner'){
            return $this->loginUserAccountApp($request, Role::OWNER);
        }
            
        return $this->loginUserAccountApp($request, Role::DRIVER);

    }



    /**
    * Social login/signup (User role) for mobile apps.
    *
    * Client should send provider token(s):
    * - Google: id_token OR access_token
    * - Facebook: access_token
     * - Apple: id_token / identity_token
    *
    * Apple first sign-in can also send:
    * - email
    * - name OR given_name + family_name
    * - user_identifier (Apple userIdentifier fallback)
    *
    * Optional for signup: mobile (+ country) if account doesn't exist yet.
    */
    public function socialLoginOrSignupUser(Request $request, $provider, SocialIdentityService $social)
    {
        $provider = strtolower((string) $provider);
        if (!in_array($provider, ['google', 'facebook', 'fb', 'apple'], true)) {
            return $this->respondBadRequest('Unsupported provider');
        }

        Log::info('mobile_social_auth_request_received', [
            'provider' => $provider,
            'login_by' => $request->input('login_by'),
            'mobile' => $this->maskMobileForLog((string) $request->input('mobile', '')),
            'country' => $request->input('country'),
            'email_present' => $request->filled('email'),
            'name_present' => $request->filled('name'),
            'given_name_present' => $request->filled('given_name') || $request->filled('givenName'),
            'family_name_present' => $request->filled('family_name') || $request->filled('familyName'),
            'user_identifier_present' => $request->filled('user_identifier') || $request->filled('userIdentifier'),
            'identity_token_present' => $request->filled('identity_token') || $request->filled('identityToken'),
            'authorization_code_present' => $request->filled('authorization_code') || $request->filled('authorizationCode'),
            'device_token_present' => $request->filled('device_token'),
            'apn_token_present' => $request->filled('apn_token'),
            'raw_keys' => array_keys($request->all()),
        ]);

        $request->validate([
            'id_token' => 'sometimes|required|string',
            'identity_token' => 'sometimes|required|string',
            'access_token' => 'sometimes|required|string',
            'oauth_token' => 'sometimes|required|string',
            'authorization_code' => 'sometimes|required|string',
            'mobile' => 'sometimes|required|string',
            'country' => 'sometimes|nullable',
            'name' => 'sometimes|nullable|string',
            'given_name' => 'sometimes|nullable|string',
            'family_name' => 'sometimes|nullable|string',
            'email' => 'sometimes|nullable|email',
            'user_identifier' => 'sometimes|nullable|string',
            'device_token' => 'sometimes|nullable|string',
            'apn_token' => 'sometimes|nullable|string',
            'login_by' => 'sometimes|nullable|in:android,ios,web',
            'profile_picture' => 'sometimes'
        ]);

        try {
            $tokenPayload = $this->socialTokenPayload($request);
            Log::info('mobile_social_auth_tokens_normalized', [
                'provider' => $provider,
                'token_keys' => array_keys($tokenPayload),
                'id_token_preview' => $this->maskTokenForLog((string) ($tokenPayload['id_token'] ?? '')),
                'identity_token_preview' => $this->maskTokenForLog((string) ($tokenPayload['identity_token'] ?? '')),
                'authorization_code_preview' => $this->maskTokenForLog((string) ($tokenPayload['authorization_code'] ?? '')),
            ]);

            $profile = $social->fetchProfile($provider, $tokenPayload);
            Log::info('mobile_social_auth_profile_fetched', [
                'provider' => $provider,
                'provider_id_preview' => $this->maskTokenForLog((string) ($profile['provider_id'] ?? '')),
                'has_email' => !empty($profile['email']),
                'has_name' => !empty($profile['name']),
                'has_avatar' => !empty($profile['avatar']),
            ]);

            $profile = $this->normalizeAppleProfile($provider, $profile, $request);
            Log::info('mobile_social_auth_profile_normalized', [
                'provider' => $provider,
                'provider_id_preview' => $this->maskTokenForLog((string) ($profile['provider_id'] ?? '')),
                'email' => $profile['email'] ?? null,
                'name' => $profile['name'] ?? null,
            ]);

            $user = $social->findOrCreateUserForRole($profile, Role::USER, [
                'mobile' => $request->input('mobile'),
                'country' => $request->input('country'),
                'name' => $profile['name'] ?? $request->input('name'),
                'email' => $profile['email'] ?? $request->input('email'),
                'device_token' => $request->input('device_token'),
                'apn_token' => $request->input('apn_token'),
                'login_by' => $request->input('login_by'),
                'profile_picture' => $request->input('profile_picture'),
                'apple_user_identifier' => $request->input('user_identifier'),
            ]);
            Log::info('mobile_social_auth_user_resolved', [
                'provider' => $provider,
                'user_id' => $user->id ?? null,
                'social_provider' => $user->social_provider ?? null,
                'social_id_preview' => $this->maskTokenForLog((string) ($user->social_id ?? '')),
                'email' => $user->email ?? null,
                'mobile' => $this->maskMobileForLog((string) ($user->mobile ?? '')),
                'login_by' => $user->login_by ?? null,
            ]);
        } catch (\RuntimeException $e) {
            Log::warning('Mobile social auth failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'mobile' => $this->maskMobileForLog((string) $request->input('mobile', '')),
                'identity_token_present' => $request->filled('identity_token') || $request->filled('identityToken'),
                'authorization_code_present' => $request->filled('authorization_code') || $request->filled('authorizationCode'),
            ]);
            if ($e->getMessage() === 'mobile_required') {
                return $this->respondBadRequest('Mobile number is required to complete signup');
            }
            if ($e->getMessage() === 'social_identifier_already_linked') {
                return $this->respondBadRequest('Mail or mobile number already with another account');
            }
            if ($e->getMessage() === 'social_account_already_linked') {
                return $this->respondBadRequest('This social account is already linked to another user');
            }
            return $this->respondBadRequest('Invalid social token');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->respondBadRequest('Unable to login with social provider');
        }

        if (!$user->isActive()) {
            $this->throwAccountDisabledException('oauth_token');
        }

        // Issue Sanctum token for the app.
        Log::info('mobile_social_auth_login_success', [
            'provider' => $provider,
            'user_id' => $user->id ?? null,
            'social_provider' => $user->social_provider ?? null,
            'social_id_preview' => $this->maskTokenForLog((string) ($user->social_id ?? '')),
        ]);
        return $this->authenticateAndRespond($user, $request, true);
    }

    private function normalizeAppleProfile(string $provider, array $profile, Request $request): array
    {
        if ($provider !== 'apple') {
            return $profile;
        }

        $givenName = trim((string) $this->requestValue($request, ['given_name', 'givenName'], ''));
        $familyName = trim((string) $this->requestValue($request, ['family_name', 'familyName'], ''));
        $displayName = trim((string) ($this->requestValue($request, ['name'], '') ?: trim($givenName . ' ' . $familyName)));

        if ($displayName !== '' && empty($profile['name'])) {
            $profile['name'] = $displayName;
        }

        if (empty($profile['email']) && $this->requestHasAny($request, ['email'])) {
            $profile['email'] = (string) $this->requestValue($request, ['email']);
        }

        if (empty($profile['provider_id'])) {
            $userIdentifier = (string) $this->requestValue($request, ['user_identifier', 'userIdentifier'], '');
            if ($userIdentifier !== '') {
                $profile['provider_id'] = $userIdentifier;
            }
        }

        return $profile;
    }

    private function socialTokenPayload(Request $request): array
    {
        return array_filter([
            'id_token' => $request->input('id_token') ?: $request->input('idToken'),
            'identity_token' => $request->input('identity_token') ?: $request->input('identityToken'),
            'access_token' => $request->input('access_token') ?: $request->input('accessToken'),
            'oauth_token' => $request->input('oauth_token') ?: $request->input('oauthToken'),
            'authorization_code' => $request->input('authorization_code') ?: $request->input('authorizationCode'),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function requestValue(Request $request, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if ($request->filled($key) || $request->has($key)) {
                return $request->input($key);
            }
        }

        return $default;
    }

    private function requestHasAny(Request $request, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($request->filled($key) || $request->has($key)) {
                return true;
            }
        }

        return false;
    }

    private function maskTokenForLog(string $value, int $visible = 6): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (strlen($value) <= $visible * 2) {
            return substr($value, 0, max(0, $visible)) . '***';
        }

        return substr($value, 0, $visible) . '...' . substr($value, -$visible);
    }

    private function maskMobileForLog(string $mobile): string
    {
        $mobile = trim($mobile);
        if ($mobile === '') {
            return '';
        }

        return strlen($mobile) <= 4 ? '****' : substr($mobile, 0, 2) . str_repeat('*', max(0, strlen($mobile) - 4)) . substr($mobile, -2);
    }




    /**
     * Logout the user based on their access token.
     * @group User-Login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @response {"success":true,"message":"success"}
     */
    public function logout(Request $request,Database $database)
    {   
        $user = auth()->user();

        $user->fcm_token=null;
        $user->save();
        if (access()->hasRole('driver')) {
            (new OnlineOfflineController($user->driver,$database))->toggle();
    
        }
        
        auth()->user()->tokens()->delete();

        return $this->respondSuccess();
    }

    /**
     * Send the OTP for user login.
     * @group User-Login
     * @param \App\Http\Requests\Auth\SendLoginOTPRequest $request
     * @bodyParam mobile string required mobile of the user entered
     * @return \Illuminate\Http\JsonResponse
     * @response {"success":true,"message":"success","uuid":"54e4ebe54er5e45re5ber54r5r5rr"}
     */
    public function sendUserLoginOTP(SendLoginOTPRequest $request)
    {
        $field = 'mobile';

        $mobile = $request->input($field);

        $user = $this->resolveUserFromMobile($mobile, Role::USER);

        $this->validateUser($user, "User with that mobile number doesn't exist.", $field);

        if (!$user->createOTP()) {
            $this->throwSendOTPErrorException($field);
        }

        $otp = $user->getCreatedOTP();
        /**
        * Send OTP here
        * Temporary logger
        */
        // \Log::info("Login OTP for {$mobile} is : {$otp}");

        return $this->respondSuccess(['uuid' => $user->getCreatedOTPUuid()]);
    }

    /**
     * Validate the user model and their account status.
     *
     * @param \App\Models\User|null $user
     * @param string $message
     * @param string|null $field
     */
    protected function validateUser($user, $message, $field = null)
    {
        if (!$user) {
            $this->throwCustomException($message, $field);
        }

        if (!$user->isActive()) {
            $this->throwAccountDisabledException($field);
        }
    }


/**
 * Send Mobile Otp
 * @group User-Login
 * @bodyParam mobile string optional mobile of the user entered
 * @bodyparam country_code string Country Code of the user mobile
 * @response
 * {
 *     "success": true,
 *     "message": "success",
 * }
 */
public function mobileOtp(Request $request)
{
    $mobile = $request->mobile;
    $otp = rand(100000, 999999);

    if(env('APP_FOR')=='demo'){

        $otp = '123456';
    }

    $country_code = $request->country_code;

// Log::info("Sms Api Calls");

    // Check if an OTP already exists for the given mobile number
    $existingOtp = MobileOtp::where('mobile', $mobile)->first();

    if ($existingOtp) {
        // Update the existing record with the new OTP
        $existingOtp->otp = $otp;
        $existingOtp->updated_at = now();
        $existingOtp->save();
    } else {
        // Create a new record if no existing record is found
        MobileOtp::create(['mobile' => $mobile, 'otp' => $otp]);
    }

    if(env('APP_FOR')=='demo'){

        return $this->respondSuccess();
    }

    $active_sms_gateway = get_active_sms_settings();
// Log::info("Active sms gatway".$active_sms_gateway);

    if (method_exists($this, $method = $active_sms_gateway)) {
         return $user = $this->{$method}($mobile, $otp, $country_code);
    }

    return $this->respondFailed();
}

//sms India Hub
    public function enable_sms_india_hub($mobile,$otp,$country_code)
    {
// Log::info("sms India Hub");

        $apiKey = get_sms_settings('sms_india_hub_api_key');
        $sid = get_sms_settings('sms_india_hub_sid');
        // dd($apiKey);
        $msisdn = "91".$mobile;
        
        $msg = "Dear User, your wait is finally over! Your account OTP is $otp.";
        $fl = '0';
        $gwid = '2';


        $response = Http::get('http://cloud.smsindiahub.in/vendorsms/pushsms.aspx', [
            'APIKey' => $apiKey,
            'msisdn' => $msisdn,
            'sid' => $sid,
            'msg' => $msg,
            'fl' => $fl,
            'gwid' => $gwid,
        ]);

        $result = json_decode($response->body(),true);


        if (isset($result['ErrorCode'])) {
            // log the error for debugging
            \Log::error('SMS API Error', $result);

            return response()->json([
                'success' => false,
                'message' => $result['ErrorMessage'] ?? 'Unknown error',
                'error_code' => $result['ErrorCode'] ?? null,
            ], 400); // or 422 depending on your case
        }


        Log::info('India hub API Response', [
            'status'  => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body'    => $result,
        ]);
        return $this->respondSuccess();
     } 
//sparrow
    public function enable_sparrow($mobile,$otp,$country_code)
    {
        /*Note
        #make sure you updated server Ip addres in saprrow sms gateway  portal
        */
        $msg = "Dear User, your wait is finally over! Your account OTP is $otp.";

        $token = get_sms_settings('sparrow_sender_id');
        $id = get_sms_settings('sparrow_token');
        // dd($id);

        // @TODO implement send sms
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.sparrowsms.com/v2/sms/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "token=$token.M4Wm&from=$id&to=".$mobile."&text=".$msg);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        $server_output = curl_exec($ch);

        curl_close($ch); 
// dd($ch);
        $status = json_decode($server_output);
            // dd($status->status);

        if($status->status!=200)
        {

          return response()->json(['success'=>false,'message'=>$status->message]);

        }else{

           return $this->respondSuccess();  
        } 
        return $this->respondFailed();

    }
//twilio
//twilio
    public function enable_twilio($mobile,$otp,$country_code)
    {


        $msg = "Dear User, your wait is finally over! Your account OTP is $otp.";

        $twilioSid = get_sms_settings('twilio_sid');
        $twilioToken = get_sms_settings('twilio_token');
        $twilioPhoneNumber = get_sms_settings('twilio_mobile_number');

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json";

        $postData = [
            'From' => $twilioPhoneNumber,
            'To' => $country_code.$mobile,
            'Body' => $msg,
        ];
        

        $postFields = http_build_query($postData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_USERPWD, "{$twilioSid}:{$twilioToken}");

        $response = curl_exec($ch);
       
        curl_close($ch);

        $status = json_decode($response);
            // dd($status->status);

        if($status->status!= "queued")
        {

          return response()->json(['success'=>false,'message'=>$status->message]);

        }else{

           return $this->respondSuccess();  
        }




    } 

//kudi sms 
    public function enable_kudi_sms_api_key($mobile,$otp,$country_code)
    {


        $msg = "Dear User, your wait is finally over! Your account OTP is $otp.";

        $kudiApiKey = get_sms_settings('kudi_sms_api_key');
        $kudiSenderId = get_sms_settings('kudi_sms_sender_id');


        //  $kudiApiKey = 'HQwOrLRlB2C7YKNqgUTxaD9i3WzFbo0t6jyJ4vsP8mVk5ZMGecdXAh1fSunEpI';
        // $kudiSenderId = 'Rovv Africa';

        $url = "https://my.kudisms.net/api/corporate";

        $postData = [
            'token' => $kudiApiKey,
            'senderID' => $kudiSenderId,
            'Body' => $msg,
            'recipients'  => $country_code.$mobile,      // Single recipient (your phone)
            'message'    => "Your OTP is $otp", // Text containing the OTP
            'otp'        => $otp,
        ];


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($postData),
        ));

        $response = curl_exec($curl); 

        $err = curl_error($curl);

        // Close the cURL session
        curl_close($curl);
        $status = json_decode($response);

        // Return the response or the error
        if ($status->status == 'error') {
            return response()->json(["error" => $status]);
        } else {
           return $this->respondSuccess();
        }
        




    }

//smsala
    public function enable_sms_ala($mobile,$otp,$country_code)
    {

        $apiKey = get_sms_settings('smsala_api_key');
        $apiPassword = get_sms_settings('smsala_api_password');
        $smsType = "P";  
        $encoding = "T";  
        $senderId = get_sms_settings('smsala_sender_id');
        $phoneNumber =$country_code.$mobile;  
        $mag = "Dear User, your wait is finally over! Your OTP is. $otp"; // Replace with the message you want to send

        $client = new Client();

            $response = $client->get('http://api.smsala.com/api/SendSMS', [
                'query' => [
                    'api_id' => $apiKey,
                    'api_password' => $apiPassword,
                    'sms_type' => $smsType,
                    'encoding' => $encoding,
                    'sender_id' => $senderId,
                    'phonenumber' => $mobile,
                    'textmessage' => $mag,
                ]
            ]);

            $body = $response->getBody();
            $content = $body->getContents();

        return $this->respondSuccess();    

    }

//msg91    
    public function enable_msg91($mobile, $otp ,$country_code)
    {
        // MSG91 API details
        
        $template_id = get_sms_settings('msg91_template_id'); 
        $auth_key = get_sms_settings('msg91_auth_key'); 

        // Ensure the mobile number is prefixed with the country code
        $mobile = $country_code. $mobile;

        // Initialize cURL session
        $curl = curl_init();

        // Prepare the data to be sent in the POST request
        $postData = [
            "template_id" => $template_id,
            "short_url" => "0",
            "recipients" => [
                [
                    "mobiles" => $mobile,
                    "var" => $otp  // Ensure 'var' matches the placeholder in the template
                ]
            ]
        ];

        // Set the cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.msg91.com/api/v5/flow/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authkey: $auth_key",
                "content-type: application/json"
            ],
        ]);

        // Execute the cURL request
        $response = curl_exec($curl);
        $err = curl_error($curl);

        // Close the cURL session
        curl_close($curl);

        // Return the response or the error
        if ($err) {
            return response()->json(["error" => $err], 500);
        } else {
            // return response()->json(json_decode($response, true));
            return response()->json(['message'=>'success','success'=>true]);
        }
    }

       // infobip
public function enable_infobip($mobile, $otp, $country_code)
{
    $baseUrl = rtrim(get_sms_settings('infobip_base_url'), '/');
    $apiKey = get_sms_settings('infobip_api_key');
    $sender = get_sms_settings('infobip_sender_id');

    $fullMobile = $country_code . $mobile;

    $message = "Dear User, your OTP is $otp.";

    $payload = [
        "messages" => [
            [
                // "from" => $sender,
                "destinations" => [
                    ["to" => $fullMobile]
                ],
                "text" => $message
            ]
        ]
    ];

    $response = Http::withHeaders([
        'Authorization' => "App $apiKey",
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->post($baseUrl . "/sms/2/text/advanced", $payload);

    if (!$response->successful()) {

        \Log::error('Infobip SMS Failed', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'SMS sending failed'
        ], 400);
    }

    \Log::info('Infobip SMS Success', [
        'response' => $response->json()
    ]);

    return $this->respondSuccess();
}

// termii
// public function enable_termii($mobile, $otp, $country_code)
// {
//     $baseUrl = rtrim(get_sms_settings('termii_base_url') ?: 'https://api.ng.termii.com', '/');
//     $apiKey = get_sms_settings('termii_api_key');
//     $senderId = get_sms_settings('termii_sender_id');
//     $channel = get_sms_settings('termii_channel') ?: 'generic';
//     $type = get_sms_settings('termii_type') ?: 'plain';

//     if (!$apiKey || !$senderId) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Termii credentials are missing'
//         ], 400);
//     }

//     $fullMobile = $country_code . $mobile;
//     $message = "Dear User, your OTP is $otp.";

//     $endpoint = Str::endsWith($baseUrl, '/api')
//         ? $baseUrl . '/sms/send'
//         : $baseUrl . '/api/sms/send';

//     $payload = [
//         'to' => $fullMobile,
//         'from' => $senderId,
//         'sms' => $message,
//         'type' => $type,
//         'channel' => $channel,
//         'api_key' => $apiKey,
//     ];

//     $response = Http::asJson()->post($endpoint, $payload);

//     \Log::info('Termii SMS Request', [
//         'to' => $fullMobile,
//         'from' => $senderId,
//         'channel' => $channel,
//         'type' => $type,
//         'endpoint' => $endpoint,
//     ]);

//     if (!$response->successful()) {
//         \Log::error('Termii SMS Failed', [
//             'status' => $response->status(),
//             'body' => $response->body()
//         ]);

//         return response()->json([
//             'success' => false,
//             'message' => 'SMS sending failed'
//         ], 400);
//     }

//     $body = $response->json();
//     $status = $body['code'] ?? $body['status'] ?? null;
//     $status = is_string($status) ? strtolower($status) : $status;

//     \Log::info('Termii SMS Response', [
//         'status' => $response->status(),
//         'body' => $body,
//     ]);

//     if ($status === 'ok' || $status === 'success' || isset($body['message_id'])) {
//         return $this->respondSuccess();
//     }

//     \Log::error('Termii SMS Error', [
//         'response' => $body
//     ]);

//     return response()->json([
//         'success' => false,
//         'message' => $body['message'] ?? 'SMS sending failed'
//     ], 400);
// }

// // tremii (alias)
// public function enable_tremii($mobile, $otp, $country_code)
// {
//     return $this->enable_termii($mobile, $otp, $country_code);
// }


//validate-OTP
/**
 * Validate Mobile Otp
 * @group User-Login
 * 
 * @bodyParam mobile string optional mobile of the user entered
 * @bodyparam country_code string Country Code of the user mobile
 * @response
 * {
 *     "success": true,
 *     "message": "success",
 *     "otp" : 895579
 * }
 */
   public function validateSmsOtp(ValidateMobileOTPRequest $request)
   {
        $otp = $request->otp;
        $mobile = $request->mobile;


        //  Log::info($otp);
        // Log::info($mobile);

        $verify_otp = MobileOtp::where('mobile' ,$mobile)->where('otp', $otp)->first();

           
            // Log::info($verify_otp);

        if (env('APP_FOR') == 'demo' && $otp == '123456'){
            $row = MobileOtp::query()->firstOrNew(['mobile' => $mobile]);
            $row->otp = $otp;
            $row->verified = true;
            $row->save();

            return $this->respondSuccess(['otp' => $otp]);
        }
        if (!$verify_otp) 
        {
            
            // Log::info($otp);
            // Log::info($mobile);

            $this->throwCustomValidationException(['message' => "The otp provided has Invaild" ]);
        }

        $verify_otp ->update(['verified' => true]);

        return $this->respondSuccess(['otp' => $verify_otp]);

   }

 




}
