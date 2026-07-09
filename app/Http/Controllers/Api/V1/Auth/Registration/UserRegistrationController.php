<?php

namespace App\Http\Controllers\Api\V1\Auth\Registration;

use DB;
use Twilio;
use App\Models\User;
use App\Models\Country;
use Illuminate\Http\Request;
use App\Events\Auth\UserLogin;
use App\Base\Constants\Auth\Role;
use App\Events\Auth\UserRegistered;
use Illuminate\Support\Facades\Log;
use App\Base\Libraries\SMS\SMSContract;
use App\Http\Controllers\ApiController;
use App\Helpers\Exception\ExceptionHelpers;
use App\Jobs\Notifications\OtpNotification;
use Psr\Http\Message\ServerRequestInterface;
use App\Base\Constants\Masters\WalletRemarks;
use App\Jobs\Notifications\AndroidPushNotification;
use App\Base\Services\OTP\Handler\OTPHandlerContract;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Helpers\Exception\throwCustomValidationException;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use App\Http\Requests\Auth\Registration\UserRegistrationRequest;
use App\Http\Requests\Auth\Registration\SendRegistrationOTPRequest;
use App\Http\Requests\Auth\Registration\ValidateRegistrationOTPRequest;
use App\Jobs\Notifications\Auth\Registration\UserRegistrationNotification;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Jobs\Notifications\SendPushNotification;
use App\Models\Master\MailTemplate;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\Mails\SendMailNotification;
use App\Models\MailOtp;
use App\Mail\OtpMail;
use App\Http\Requests\Auth\Registration\ValidateEmailOTPRequest;
use App\Http\Requests\Auth\Registration\SendRegistrationMailOTPRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin\Driver;
use App\Models\Admin\AdminNotification;
use Kreait\Firebase\Contract\Database;
use App\Mail\UserRegistationMail;
use App\Jobs\Mails\SendUserRegistationMailNotification;
use App\Models\Admin\Setting;
use App\Services\Auth\SocialIdentityService;

/**
 * @group SignUp-And-Otp-Validation
 *
 * APIs for User-Management
 */
class UserRegistrationController extends LoginController
{
    use ExceptionHelpers;
    /**
     * The OTP handler instance.
     *
     * @var \App\Base\Services\OTP\Handler\OTPHandlerContract
     */
    protected $otpHandler;

    /**
     * The user model instance.
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * The SMS contract instance.
     *
     * @var \App\Base\Libraries\SMS\SMSContract
     */
    protected $smsContract;

    /**
     * The image uploader instance.
     *
     * @var \App\Base\Services\ImageUploader\ImageUploaderContract
     */
    protected $imageUploader;

    /**
     * The country model instance.
     *
     * @var \App\Models\Country
     */
    protected $country;

    /**
     * The country model instance.
     *
     * @var \Kreait\Firebase\Contract\Database
     */
    protected $database;


    /**
     * UserRegistrationController constructor.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Country $country
     * @param \App\Base\Libraries\SMS\SMSContract $smsContract
     * @param \App\Base\Services\ImageUploader\ImageUploaderContract $imageUploader
     * @param \App\Base\Services\OTP\Handler\OTPHandlerContract $otpHandler
     * @param \Kreait\Firebase\Contract\Database $database;
     */
    public function __construct(User $user, OTPHandlerContract $otpHandler, Country $country,
    SMSContract $smsContract,
    ImageUploaderContract $imageUploader,
    Database $database)
    {
        $this->user = $user;
        $this->otpHandler = $otpHandler;
        $this->country = $country;
        $this->smsContract = $smsContract;
        $this->imageUploader = $imageUploader;
        $this->database = $database;

    }
    /**
     * Send the email verification OTP during registration.
     * @bodyParam email required string 
     * @bodyParam otp string required Provided otp
     *
     * @param \App\Http\Requests\Auth\Registration\SendRegistrationMailOTPRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @response {"success":true,"message":"success"}
     */
    public function sendMailOTP(SendRegistrationMailOTPRequest $request)
    {

        $email = $request->input('email');

        $mail_otp_exists =  MailOtp::where('email', $email)->exists();
        $otp = mt_rand(100000, 999999);
        if(env('APP_FOR') == 'demo') {
            $otp = 123456;
        }

        if($mail_otp_exists == false)
        {

        $newOTP = MailOtp::create([
            'email' => $email,
            'otp' => $otp,
            'verified' => false,
        ]);


          Mail::to($email)->send(new OtpMail($otp));

        }else{

           $mailOtp = MailOtp::where('email', $email)->first();


           $mailOtp->update(['otp' => $otp,'verified'=>false]);

           Mail::to($email)->send(new OtpMail($otp));

        }
        return response()->json(['success'=>true]);

    }
    /**
     * Validate the mobile number verification OTP during registration.
     * @bodyParam otp string required Provided otp
     * @bodyParam uuid uuid required uuid comes from sen otp api response
     *
     * @param \App\Http\Requests\Auth\Registration\ValidateRegistrationOTPRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @response {"success":true,"message":"success"}
     */
    public function validateEmailOTP(ValidateEmailOTPRequest $request)
    {
        $otp = $request->otp;
        $email = $request->email;

        $verify_otp = MailOtp::where('email' ,$email)->where('otp', $otp)->exists();


        if ($verify_otp == false)
        {
            $this->throwCustomValidationException(['message' => "The otp provided has Invaild" ]);
        }

        MailOtp::where('email' ,$email)->where('otp', $otp)->update(['verified' => true]);

        return response()->json(['success'=>true]);
    }
    /**
     * Register the user and send welcome email.
     * @bodyParam name string required name of the user
    * @bodyParam company_key string optional company key of demo
     * @bodyParam mobile integer required mobile of user
     * @bodyParam email email required email of the user
     * @bodyParam password password required password provided user
     * @bodyParam oauth_token string optional from social provider
     * @bodyParam password_confirmation password required  confirmed password provided user
     * @bodyParam device_token string required device_token of the user
     * @bodyParam refferal_code string optional refferal_code of the another user
     * @bodyParam login_by string required from which device the user registered. the input should be 'android',or 'ios'
     * @param \App\Http\Requests\Auth\Registration\UserRegistrationRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @responseFile responses/auth/register.json
     */
    public function register(UserRegistrationRequest $request)
    {
        $mobileUuid = $request->input('uuid');


        if(str_contains($request->country, '+')){
            $country_data = $this->country->whereActive(true)->where('dial_code',$request->country)->first();
        }else{
            $country_data = $this->country->where('code',$request->country)->first();
        }

        if($request->email !=null){

             $validate_exists_email = $this->user->belongsTorole(Role::USER)->where('email', $request->email)->exists();

        if ($validate_exists_email) {

             if($request->is_web){

                $user = $this->user->belongsTorole(Role::USER)->where('email', $request->email)->first();

                return $this->authenticateAndRespond($user, $request, $needsToken=true);

            }
            $this->throwCustomException('Provided email has already been taken');
        }


        }

       

        // $mobile = $this->otpHandler->getMobileFromUuid($mobileUuid);
        $mobile = $request->mobile;

        $validate_exists_mobile = $this->user->belongsTorole(Role::USER)->where('mobile', $mobile)->exists();

        if ($validate_exists_mobile) {

            if($request->is_web){

                $user = $this->user->belongsTorole(Role::USER)->where('mobile', $mobile)->first();

                return $this->authenticateAndRespond($user, $request, $needsToken=true);

            }
            $this->throwCustomException('Provided mobile has already been taken');
        }

        if (!$country_data) {
            $this->throwCustomException('unable to find country');
        }
        $country_id = $country_data->id;


        if ($request->has('refferal_code')) {
            // Validate Referral code
            $referred_user_record = $this->user->belongsTorole(Role::USER)->where('refferal_code', $request->refferal_code)->first();
            if (!$referred_user_record) {
                $this->throwCustomException('Provided Referral code is not valid', 'refferal_code');
            }
            // Add referral commission to the referred user
            $this->addCommissionToRefferedUser($referred_user_record);
        }

        $profile_picture = null;

        if ($uploadedFile = $this->getValidatedUpload('profile_picture', $request)) {
            $profile_picture = $this->imageUploader->file($uploadedFile)
                ->saveProfilePicture();
        }
        if ($request->has('email_confirmed') == true)
        {
            $user_params['email_confirmed']= true;
        }
        // DB::beginTransaction();
        // try {
        $user_params = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'gender' => $request->input('gender'),
            'mobile' => $mobile,
            'mobile_confirmed' => true,
            'fcm_token'=>$request->input('device_token'),
            'login_by'=>$request->input('login_by'),
            'country'=>$country_id,
            'refferal_code'=>str_random(6),
            'profile_picture'=>$profile_picture,
            'lang'=>$request->input('lang'),
        ];


        $user_params['ride_otp']=rand(1111, 9999);

        if (env('APP_FOR')=='demo') 
        {
            $user_params['ride_otp']=0000;
        }
        if($request->has('is_bid_app')){

            $user_params['is_bid_app']=1;
        }

        if (env('APP_FOR')=='demo' && $request->has('company_key') && $request->input('company_key')) {
            $user_params['company_key'] = $request->input('company_key');
        }
        if ($request->has('password') && $request->input('password')) {
            $user_params['password'] = bcrypt($request->input('password'));
        }

        
        // Get setting
        $enableUserDocUpload = Setting::where('name', 'enable_user_document_upload')->value('value');

        // Set approve status
        $user_params['approve'] = $enableUserDocUpload == 1 ? 0 : 1;
        $user = $this->user->create($user_params);

        Log::info('mobile_registration_user_created', [
            'user_id' => $user->id ?? null,
            'email' => $user->email ?? null,
            'mobile' => $this->maskMobileForLog((string) ($user->mobile ?? '')),
            'login_by' => $user->login_by ?? null,
            'social_provider' => $user->social_provider ?? null,
            'social_id_preview' => $this->maskTokenForLog((string) ($user->social_id ?? '')),
        ]);

        // Create Empty Wallet to the user
        $user->userWallet()->create(['amount_added'=>0]);

        $user->attachRole(Role::USER);

        // $this->dispatch(new UserRegistrationNotification($user));

        event(new UserRegistered($user));

        if (
            $request->filled('oauth_token')
            || $request->filled('id_token')
            || $request->filled('access_token')
            || $request->filled('identity_token')
            || $request->filled('identityToken')
            || $request->filled('authorization_code')
            || $request->filled('authorizationCode')
        ) {
            try {
                $provider = $this->resolveSocialProvider($request);
                $social = app(SocialIdentityService::class);
                $tokenPayload = $this->socialTokenPayload($request);
                Log::info('mobile_registration_social_tokens_normalized', [
                    'provider' => $provider,
                    'token_keys' => array_keys($tokenPayload),
                    'id_token_preview' => $this->maskTokenForLog((string) ($tokenPayload['id_token'] ?? '')),
                    'identity_token_preview' => $this->maskTokenForLog((string) ($tokenPayload['identity_token'] ?? '')),
                    'authorization_code_preview' => $this->maskTokenForLog((string) ($tokenPayload['authorization_code'] ?? '')),
                ]);

                $profile = $social->fetchProfile($provider, $tokenPayload);
                Log::info('mobile_registration_social_profile_fetched', [
                    'provider' => $provider,
                    'provider_id_preview' => $this->maskTokenForLog((string) ($profile['provider_id'] ?? '')),
                    'has_email' => !empty($profile['email']),
                    'has_name' => !empty($profile['name']),
                ]);
                $profile = $this->normalizeAppleProfile($provider, $profile, $request);
                Log::info('mobile_registration_social_profile_normalized', [
                    'provider' => $provider,
                    'provider_id_preview' => $this->maskTokenForLog((string) ($profile['provider_id'] ?? '')),
                    'email' => $profile['email'] ?? null,
                    'name' => $profile['name'] ?? null,
                ]);

                $social->linkIdentity($user, $profile['provider'], $profile['provider_id']);
                Log::info('mobile_registration_social_identity_linked', [
                    'user_id' => $user->id ?? null,
                    'provider' => $profile['provider'] ?? null,
                    'provider_id_preview' => $this->maskTokenForLog((string) ($profile['provider_id'] ?? '')),
                ]);
                $social->updateUserFromProfile($user, $profile, [
                    'device_token' => $request->input('device_token'),
                    'login_by' => $request->input('login_by'),
                ]);
                if ($this->requestHasAny($request, ['authorization_code', 'authorizationCode'])) {
                    $user->authorization_code = (string) $this->requestValue($request, ['authorization_code', 'authorizationCode']);
                    $user->save();
                    Log::info('mobile_registration_authorization_code_saved', [
                        'user_id' => $user->id ?? null,
                        'authorization_code_preview' => $this->maskTokenForLog((string) $user->authorization_code),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Social token ignored during registration', [
                    'error' => $e->getMessage(),
                    'provider' => $provider ?? null,
                    'mobile' => $this->maskMobileForLog((string) $request->input('mobile', '')),
                ]);
            }
        }
        //     DB::commit();
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Log::error($e);
        //     Log::error('Error while Registering a user account. Input params : ' . json_encode($request->all()));
        //     return $this->respondBadRequest('Unknown error occurred. Please try again later or contact us if it continues.');
        // }

        // $url = env('APP_URL') . "users/view-profile/" . $user->id;

        $url = route('users.view-profile', ['user' => $user->id]);


        $uuid = uniqid(); // Generate unique ID for both Firebase and MySQL
        
        $notificationData = [
            'id' => $uuid, // Use the same UUID for both Firebase and MySQL
            'body' => "New User Registered",
            'title' => "New User Registered",
            'read' => false,
            'updated_at' => round(microtime(true) * 1000),
            'url' => $url
        ];
        
        // Insert into Firebase
        $this->database->getReference('admin-notification/' . $uuid)
                       ->set($notificationData);


        if ($user->email !=null) {
            SendUserRegistationMailNotification::dispatch($user);
        }

        if ($user) {
            
            return $this->authenticateAndRespond($user, $request, $needsToken=true);
        }
        return $this->respondBadRequest('Unknown error occurred. Please try again later or contact us if it continues.');

        // return $this->respondSuccess();
    }

    private function resolveSocialProvider(Request $request): string
    {
        $provider = strtolower((string) $this->requestValue($request, ['oauth_provider', 'social_provider', 'provider', 'oauthProvider'], ''));

        if ($provider !== '') {
            return $provider;
        }

        if ($this->requestHasAny($request, ['identity_token', 'identityToken', 'authorization_code', 'authorizationCode'])) {
            return 'apple';
        }

        return 'google';
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
            'id_token' => $this->requestValue($request, ['id_token', 'idToken']),
            'identity_token' => $this->requestValue($request, ['identity_token', 'identityToken']),
            'access_token' => $this->requestValue($request, ['access_token', 'accessToken']),
            'oauth_token' => $this->requestValue($request, ['oauth_token', 'oauthToken']),
            'authorization_code' => $this->requestValue($request, ['authorization_code', 'authorizationCode']),
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
    * Validate Mobile-For-User
    * @bodyParam mobile integer required mobile of user
     * @response {
     * "success":true,
     * "message":"mobile_validated",
     * }
     * @response {
     * "success":true,
     * "message":"email_does_not_exists",
     * }
    *
    */
    public function validateUserMobile(Request $request)
    {
  if ($request->has('mobile'))
    {
        $mobile = $request->mobile;

        $validate_exists_mobile = $this->user->belongsTorole(Role::USER)->where('mobile', $mobile)->exists();

        if ($validate_exists_mobile) {
            $this->throwCustomException('Provided mobile has already been taken');
        }

        return $this->respondSuccess(null, 'mobile_validated');
      }
      if ($request->has('email'))
         {
            $email = $request->input('email');

            $validate_exists_email = $this->user->belongsTorole(Role::USER)->where('email', $email)->exists();
            if ($validate_exists_email)
            {
                return $this->respondFailed('email_exists');
            }

         return $this->respondSuccess('email_does_not_exists');

        }


    }
    /**
    * Validate Mobile-For-User-Login
    * @bodyParam mobile integer required mobile of user
    * @response {
    * "success":true,
    * "message":"mobile_exists",
    * }
    *
    */
     public function validateUserMobileForLogin(Request $request)
    {

    if ($request->has('mobile') && $request->has('email')) {
        $mobile = $request->mobile;
        $email = $request->email;

        $existsMobile = $this->user->belongsTorole(Role::USER)->where('mobile', $mobile)->exists();
        $existsEmail = $this->user->belongsTorole(Role::USER)->where('email', $email)->exists();

        if ($existsMobile && $existsEmail) {
            // Both mobile and email exist
            return $this->respondSuccess(null, 'mobile_and_email_exist');
        } elseif ($existsMobile) {
            // Only mobile exists
            return $this->respondSuccess(null, 'mobile_exists');
        } elseif ($existsEmail) {
            // Only email exists
            return $this->respondSuccess(null, 'email_exists');
        } else {
            // Neither mobile nor email exist
           return $this->respondFailed('email_does_not_exists');

        }

      }

      if ($request->has('mobile'))
        {

        $mobile = $request->mobile;

        $validate_exists_mobile = $this->user->belongsTorole(Role::USER)->where('mobile', $mobile)->exists();

        if($request->has('role') && $request->role=='user'){

        $validate_exists_mobile = $this->user->belongsTorole(Role::USER)->where('mobile', $mobile)->exists();

        }

        if ($validate_exists_mobile) {
            return $this->respondSuccess(null, 'mobile_exists');
        }

        return $this->respondFailed('email_does_not_exists');

       }
      if ($request->has('email'))
        {

        $email = $request->email;

        $validate_exists_email = $this->user->belongsTorole(Role::USER)->where('email', $email)->exists();

        if($request->has('role') && $request->role=='user'){

        $validate_exists_email = $this->user->belongsTorole(Role::USER)->where('email', $email)->exists();

        }

        if ($validate_exists_email) {
            return $this->respondSuccess(null, 'email_exists');
        }

        return $this->respondFailed('email_does_not_exists');
      }


    }

    /**
    * Add Commission to the referred user
    *
    */
    public function addCommissionToRefferedUser($reffered_user)
    {
        $user_wallet = $reffered_user->userWallet;
        $referral_commision = get_settings('referral_commision_for_user')?:0;

        $user_wallet->amount_added += $referral_commision;
        $user_wallet->amount_balance += $referral_commision;
        $user_wallet->save();

        // Add the history
        $reffered_user->userWalletHistory()->create([
            'amount'=>$referral_commision,
            'transaction_id'=>str_random(6),
            'remarks'=>WalletRemarks::REFERRAL_COMMISION,
            'refferal_code'=>$reffered_user->refferal_code,
            'is_credit'=>true]);

        // Notify user
        // $title = custom_trans('referral_earnings_notify_title');
        // $body = custom_trans('referral_earnings_notify_body');

        // $title = custom_trans('referral_earnings_notify_title',[],$reffered_user->user->lang);
        // $body = custom_trans('referral_earnings_notify_body',[],$reffered_user->user->lang);

        // dispatch(new SendPushNotification($reffered_user,$title,$body));

        $notification = \DB::table('notification_channels')
                ->where('topics', 'User Referral') // Match the correct topic
                ->first();

            //    send push notification 
                if ($notification && $notification->push_notification == 1) {
                     // Determine the user's language or default to 'en'
                    $userLang = $reffered_user->user->lang ?? 'en';
                    // dd($userLang);
    
                    // Fetch the translation based on user language or fall back to 'en'
                    $translation = \DB::table('notification_channels_translations')
                        ->where('notification_channel_id', $notification->id)
                        ->where('locale', $userLang)
                        ->first();
    
                    // If no translation exists, fetch the default language (English)
                    if (!$translation) {
                        $translation = \DB::table('notification_channels_translations')
                            ->where('notification_channel_id', $notification->id)
                            ->where('locale', 'en')
                            ->first();
                    }            
                    
                    $title =  $translation->push_title ?? $notification->push_title;
                    $body = strip_tags($translation->push_body ?? $notification->push_body);
                    dispatch(new SendPushNotification($reffered_user, $title, $body));
                }

    }


    /**
     * Send the mobile number verification OTP during registration.
     * @bodyParam country string required dial_code of the country
     * @bodyParam mobile int required Mobile of the user
     * @bodyParam email string required Email of the user
     * @param \App\Http\Requests\Auth\Registration\SendRegistrationOTPRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @response {
     * "success":true,
     * "message":"success",
     * "message_keyword":"otp_sent_successfuly"
     * "data":{
     * "uuid":"6ffa38d1-d2ca-434a-8695-701ca22168b1"
     * }
     * }
     */
    public function sendOTP(SendRegistrationOTPRequest $request)
    {
        // dd(ceil(600.01 / 50) * 50);

        $field = 'mobile';

        $mobile = $request->input($field);

        DB::beginTransaction();
        try {
            $country_code = $this->country->where('dial_code', $request->input('country'))->exists();
            if (!$country_code) {
                $this->throwCustomValidationException('unable to find country', 'dial_code');
            }
            $mobileForOtp = $request->input('country') . $mobile;

            if (!$this->otpHandler->setMobile($mobile)->create()) {
                $this->throwSendOTPErrorException($field);
            }

            $otp = $this->otpHandler->getOtp();
            // Generate sms from template
            $sms = sms_template('generic-otp', ['otp'=>$otp,'mobile'=>$mobileForOtp], 'en');
            // Send sms by providers
            $this->smsContract->queueOn('default', $mobile, $sms);
            // $this->dispatch(new OtpNotification($mobile, $otp, $sms));

            /**
             * Send OTP here
             * Temporary logger
             */
            // Twilio::message($mobileForOtp, $message);

            \Log::info($sms);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Log::error('Error while Registering a user account. Input params : ' . json_encode($request->all()));
            return $this->respondBadRequest('Unknown error occurred. Please try again later or contact us if it continues.');
        }
        DB::commit();

        // return $this->respondSuccess(['uuid' => $this->otpHandler->getUuid()]);

        return response()->json(['success'=>true,'message'=>'success','message_keyword'=>'otp_sent_successfuly','data'=>['uuid' => $this->otpHandler->getUuid()]]);
    }

    /**
     * Validate the mobile number verification OTP during registration.
     * @bodyParam otp string required Provided otp
     * @bodyParam uuid uuid required uuid comes from sen otp api response
     *
     * @param \App\Http\Requests\Auth\Registration\ValidateRegistrationOTPRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @response {"success":true,"message":"success"}
     */
    public function validateOTP(ValidateRegistrationOTPRequest $request)
    {
        $otpField = 'otp';
        $uuidField = 'uuid';

        $otp = $request->input($otpField);
        $uuid = $request->input($uuidField);

        if (!$this->otpHandler->validate($otp, $uuid)) {
            $message = $this->otpHandler->isExpired() ?
            'The otp provided has expired.' :
            'The otp provided is invalid.';

            $this->throwCustomValidationException($message, $otpField);
        }

        // return $this->respondSuccess();
        return response()->json(['success'=>true,'message'=>'success','message_keyword'=>'otp_validated_successfuly']);
    }
    /**
     * Update password for user
     * @bodyParam email string optional User email
     * @bodyParam mobile string optional User mobile
     * @bodyParam password string required New password to be set
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @response {"success":true,"message":"password_updated_successfuly"}
     */
    public function updatePassword(Request $request)
{
 
    $user = User::whereHas('roles', function ($q) {
            $q->where('slug', Role::USER); // OR ->where('id', Role::USER)
        })
        ->where(function ($q) use ($request) {
            if ($request->filled('email')) {
                $q->where('email', $request->email);
            }

            if ($request->filled('mobile')) {
                $q->where('mobile', $request->mobile);
            }
        })
        ->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found'
        ], 404);
    }

    $user->update([
        'password' => Hash::make($request->password)
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Password updated successfully'
    ]);
}
}
