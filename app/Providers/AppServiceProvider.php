<?php

namespace App\Providers;

use Schema;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Validation\Factory as Validator;
use Illuminate\Pagination\Paginator;
use App\Models\Admin\LandingHeader;
use App\Models\Admin\LandingHome;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Factory as Firebase;
use App\Models\ThirdPartySetting;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{

// use Braintree\Configuration as Braintree_Configuration;
    /**
     * Validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected $validator;

    /**
     * Bootstrap any application services.
     *
     * @param \Illuminate\Contracts\Validation\Factory $validator
     * @return void
     */
    public function boot(Validator $validator)
    {

        Inertia::share([
            'app_for' => env('APP_FOR')
        ]);
        
        $this->validator = $validator;

        Schema::defaultStringLength(191);

        $this->loadCustomValidators();

        Paginator::useBootstrap();

        if (Schema::hasTable('third_party_settings')) {
            $firebase_database_url = ThirdPartySetting::where('name','firebase_database_url')->pluck('value')->first();
        } else {
            // Handle missing table (e.g., log a warning or create the table)
            $firebase_database_url = 'https://your-firebase-db.firebaseio.com/';
        }

        $firebaseCredentialsPath = public_path('push-configurations/firebase.json'); // Get full path

        if(!$firebase_database_url){
            
            $firebase_database_url = 'https://your-firebase-db.firebaseio.com/';

        }


    if ($firebase_database_url) {
        
        config([
            'firebase.projects.app.credentials' => $firebaseCredentialsPath,
            'firebase.projects.app.database.url' => $firebase_database_url,
        ]);


        $firebase = (new Firebase)
            ->withServiceAccount($firebaseCredentialsPath)
            ->withDatabaseUri($firebase_database_url);
        
        app()->instance('firebase', $firebase);
    }

        // Social auth credentials from third_party_settings (keeps parity with mail/firebase settings).
        if (Schema::hasTable('third_party_settings')) {
            $social = ThirdPartySetting::where('module', 'social-auth')->pluck('value', 'name')->toArray();
            $googleClientId = trim((string) ($social['google_client_id'] ?? ''));
            $googleClientSecret = trim((string) ($social['google_client_secret'] ?? ''));
            $facebookClientId = trim((string) ($social['facebook_client_id'] ?? ''));
            $facebookClientSecret = trim((string) ($social['facebook_client_secret'] ?? ''));
            $appleClientId = trim((string) ($social['apple_client_id'] ?? ''));
            $appleTeamId = trim((string) ($social['apple_team_id'] ?? ''));
            $appleKeyId = trim((string) ($social['apple_key_id'] ?? ''));
            $applePrivateKey = $this->normalizeApplePrivateKey((string) ($social['apple_private_key'] ?? ''));

            $useGoogleSettings = $googleClientId !== '' && $googleClientSecret !== '';
            $useFacebookSettings = $facebookClientId !== '' && $facebookClientSecret !== '';
            $useAppleSettings = $appleClientId !== '' && $appleTeamId !== '' && $appleKeyId !== '' && $applePrivateKey !== '';

            config([
                'services.google.client_id' => $useGoogleSettings ? $googleClientId : env('GOOGLE_CLIENT_ID'),
                'services.google.client_secret' => $useGoogleSettings ? $googleClientSecret : env('GOOGLE_CLIENT_SECRET'),
                'services.facebook.client_id' => $useFacebookSettings ? $facebookClientId : env('FACEBOOK_CLIENT_ID'),
                'services.facebook.client_secret' => $useFacebookSettings ? $facebookClientSecret : env('FACEBOOK_CLIENT_SECRET'),
                'services.apple.client_id' => $useAppleSettings ? $appleClientId : env('APPLE_CLIENT_ID'),
                'services.apple.team_id' => $useAppleSettings ? $appleTeamId : env('APPLE_TEAM_ID'),
                'services.apple.key_id' => $useAppleSettings ? $appleKeyId : env('APPLE_KEY_ID'),
                'services.apple.private_key' => $useAppleSettings
                    ? $applePrivateKey
                    : $this->normalizeApplePrivateKey((string) env('APPLE_PRIVATE_KEY')),
            ]);
        }

        // if (Schema::hasTable('landing_headers')) {
        //     $headers = LandingHeader::first();
        //     View::share('headers', $headers);
        // } else {
        //     View::share('headers', [
        //         'home' => 'Home',
        //         'driver' => 'Driver',
        //         'user' => 'User',
        //         'contact' => 'Contact',
        //         'locale' => 'en',
        //         'language' => 'English',
        //     ]);
        // }

        if (Schema::hasTable('settings')) {

            $s = Setting::getCached();
            $v = fn (string $key, $default = null) => $s[$key] ?? $default;
            view()->share('admin_url', 'login/' . ($v('admin_login') ?? 'admin'));
            view()->share('owner_url', 'login/' . ($v('owner_login') ?? 'owner'));
            view()->share('user_url', 'login/' . ($v('user_login') ?? 'user'));
            view()->share('dispatch_url', 'login/' . ($v('dispatcher_login') ?? 'dispatcher'));
            view()->share('agent_url', 'login/' . ($v('agent_login') ?? 'agent'));
            view()->share('dispatch_pro_url', 'login/' . ($v('dispatcher_login_pro') ?? 'dispatcher_pro'));
           
            $franchise_addons = Setting::whereName('franchise-addons')->firstOrNew([]);
            view()->share('franchise_addons', $franchise_addons->value); 


            view()->share('agent_addons', $v('dispatcher-addons'));

            view()->share('franchise_url', 'login/' . ($v('franchise_login') ?? 'franchise'));
            view()->share('franchise_addons', $v('franchise-addons'));

            view()->share('supportTicket', $v('enable_support_ticket_feature'));

            view()->share('navs', $v('nav_color') ?? '#0ab39c');
            view()->share('side', $v('sidebar_color') ?? '#405189');
            view()->share('side_txt', $v('sidebar_text_color') ?? '#a2a5af');

            view()->share('landing_header_bg_color', $v('landing_header_bg_color') ?? '#ffffff');
            view()->share('landing_header_text_color', $v('landing_header_text_color') ?? '#212529');
            view()->share('landing_header_active_text_color', $v('landing_header_active_text_color') ?? '#0ab39c');
            view()->share('landing_footer_bg_color', $v('landing_footer_bg_color') ?? '#000000');
            view()->share('landing_footer_text_color', $v('landing_footer_text_color') ?? '#f1ffff');

            //single landing page
            view()->share('single_landing_header_bg_color', $v('single_landing_header_bg_color') ?? '#100a64');
            view()->share('single_landing_header_text_color', $v('single_landing_header_text_color') ?? '#fcfcfc');
            view()->share('single_landing_header_active_text_color', $v('single_landing_header_active_text_color') ?? '#100a64');
            view()->share('single_landing_footer_bg_color', $v('single_landing_footer_bg_color') ?? '#100a64');
            view()->share('single_landing_footer_text_color', $v('single_landing_footer_text_color') ?? '#fffff');


            //banner bg color
            view()->share('banner_bg_color', $v('banner_bg_color') ?? '#005555');
            view()->share('banner_title_color', $v('banner_title_color') ?? '#ffff');
            view()->share('banner_description_color', $v('banner_description_color') ?? '#ffff');
            view()->share('banner_button_color', $v('banner_button_color') ?? '#ffff');

            view()->share('footer_content1', $v('footer_content1'));
            view()->share('footer_content2', $v('footer_content2'));
            view()->share('dispatcher_sidebar_color', $v('dispatcher_sidebar_color'));
            view()->share('dispatcher_sidebar_txt_color', $v('dispatcher_sidebar_txt_color'));

            $logoVal = $v('logo');
            view()->share('logo', !empty($logoVal) ? asset('storage/uploads/system-admin/logo/' . $logoVal) : asset('storage/uploads/system-admin/logo/rest.png'));
            $faviconVal = $v('favicon');
            view()->share('favicon', !empty($faviconVal) ? asset('storage/uploads/system-admin/logo/' . $faviconVal) : asset('storage/uploads/system-admin/logo/Restart user.jpg'));
            $loginbgVal = $v('loginbg');
            view()->share('loginbg', !empty($loginbgVal) ? asset('storage/uploads/system-admin/logo/' . $loginbgVal) : asset('storage/uploads/system-admin/logo/workspace.jpg'));
            $ownerLoginbgVal = $v('owner_loginbg');
            view()->share('owner_loginbg', !empty($ownerLoginbgVal) ? asset('storage/uploads/system-admin/logo/' . $ownerLoginbgVal) : asset('storage/uploads/system-admin/logo/workspace.jpg')); 
        } else {

            view()->share('navs', "#0ab39c");

            view()->share('side', "#405189");

            view()->share('side_txt', "#a2a5af");

            view()->share('landing_header_bg_color', "#ffffff");
            view()->share('landing_header_text_color', "#212529");
            view()->share('landing_header_active_text_color', "#0ab39c");
            view()->share('landing_footer_bg_color', "#000000");
            view()->share('landing_footer_text_color', "#f1ffff");

            
            view()->share('single_landing_header_bg_color', "#101435");
            view()->share('single_landing_header_text_color', "#f7f7f7");
            view()->share('single_landing_header_active_text_color', "#101435");
            view()->share('single_landing_footer_bg_color', "#101435");
            view()->share('single_landing_footer_text_color', "#f1ffff");

            view()->share('logo', asset('storage/uploads/system-admin/logo/rest.png'));
            view()->share('favicon',asset('storage/uploads/system-admin/logo/Restart user.jpg'));
            view()->share('loginbg', asset('storage/uploads/system-admin/logo/workspace.jpg'));
        }


        if (Schema::hasTable('landing_headers')) {
            // $headers = LandingHeader::all();
            $headerSettings = Schema::hasTable('settings') ? Setting::getCached() : [];
            $headers = LandingHeader::all()->map(function ($header) use ($headerSettings) {
                $header->header_logo_url = asset('storage/uploads/website/images/' . $header->header_logo);
                $header->footer_logo_url = asset('storage/uploads/website/images/' . $header->footer_logo);
                $header->enable_web_booking = !empty($headerSettings['enable_web_booking_feature']) ? 1 : 0;
                $header->userlogin = 'login/' . ($headerSettings['user_login'] ?? 'user');
                return $header;
            });
            $locales = $headers->pluck('locale', 'id');
            View::share('headers', $headers);
            View::share('locales', $locales);
        }
        elseif (Schema::hasTable('single_landing_page')) {
            // $headers = LandingHeader::all();
            $headerSettings = Schema::hasTable('settings') ? Setting::getCached() : [];
            $headers = SingleLandingHeader::all()->map(function ($header) use ($headerSettings) {
                $header->header_logo_url = asset('storage/uploads/website/images/' . $header->header_logo);
                $header->footer_logo_url = asset('storage/uploads/website/images/' . $header->footer_logo);
                $header->enable_web_booking = !empty($headerSettings['enable_web_booking_feature']) ? 1 : 0;
                $header->userlogin = 'login/' . ($headerSettings['user_login'] ?? 'user');
                return $header;
            });
            $locales = $headers->pluck('locale', 'id');
            View::share('headers', $headers);
            View::share('locales', $locales);
        }
         else {
            $defaultHeaders = collect([
                'id' => '1',
                'header_logo' => 'rest.png',
                'header_logo_url' => asset('storage/uploads/website/images/rest.png'),
                'home' => 'Home',
                'driver' => 'Driver',
                'user' => 'User',
                'contact' => 'Contact',                
                'book_now_btn' => 'Book Now',
                'footer_logo' => 'rest.png',
                'footer_logo_url' => asset('storage/uploads/website/images/rest.png'),
                'footer_para' => 'Tagxi is a rideshare platform facilitating peer to peer ridesharing by means of connecting passengers who are in need of rides from drivers with available cars to get from point A to point B with the press of a button.',
                'quick_links' => 'Quick Links',
                'compliance' => 'Compliance',
                'privacy' => 'Privacy Policy',
                'terms' => 'Terms & Conditions',
                'dmv' => 'DMV Check',
                'user_app' => 'Use Apps',
                'user_play' => 'Play Store',
                'user_play_link' => 'https://play.google.com/store/apps/details?id=com.user.tagxi',
                'user_apple' => 'Apple Store',
                'user_apple_link' => 'misoftwares.in',
                'driver_app' => 'Driver Apps',
                'driver_play' => 'Play Store',
                'driver_play_link' => 'misoftwares.in',
                'driver_apple' => 'Apple Store',
                'driver_apple_link' => 'misoftwares.in',
                'copy_rights' => '2021 @ Misoftwares',
                'fb_link' => 'fb.com',
                'linkdin_link' => 'linkdin.com',
                'x_link' => 'x.com',
                'insta_link' => 'instagram.com',
                'locale' => 'En',
                'language' => 'English',
                'enable_web_booking' => 0,
                'userlogin' => 'login',
            ]);
            View::share('headers', $defaultHeaders);
            View::share('locales', $defaultHeaders->pluck('locale', 'id'));
        }
        // Set the locale from the session or default to 'en'
    $selectedLocale = session('selectedLocale', 'en');
    app()->setLocale($selectedLocale);

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {
           
        }
    }

    private function normalizeApplePrivateKey(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return str_replace(['\\r\\n', '\\n', '\\r'], ["\n", "\n", "\n"], $value);
    }

    /**
     * Load the custom validator methods.
     *
     * @return void
     */
    protected function loadCustomValidators()
    {
        $customValidatorClass = 'App\Base\Validators\CustomValidators';

        $this->extendValidator('mobile_number', $customValidatorClass);
        $this->extendValidator('numeric_max', $customValidatorClass);
        $this->extendValidator('numeric_min', $customValidatorClass);
        $this->extendValidator('otp', $customValidatorClass);
        $this->extendValidator('uuid', $customValidatorClass);
        $this->extendValidator('decimal', $customValidatorClass);
        $this->extendValidator('double', $customValidatorClass);
    }

    /**
     * Extend the validator with custom methods.
     *
     * @param string $name
     * @param string $class
     * @return void
     */
    protected function extendValidator($name, $class)
    {
        $method = 'validate' . Str::studly($name);

        $this->validator->extend($name, "{$class}@{$method}");
    }

    public function nav() 
    {
    
    
            $navs = Setting::whereName('nav_color')->first();
    
    
    
         view()->share('navs', $navs);
    
    
        }
    
        public function side() 
        {
        
                $side = Setting::whereName('sidebar_color')->first();
        
        
             view()->share('side', $side);
        
        
            }
    
            public function sidetxt() 
        {
        
                $side_txt = Setting::whereName('sidebar_text_color')->first();
        
        
             view()->share('side_txt', $side_txt);
        
        
            }
            public function landingbgcolor() 
            {
            
                $landing_header_bg_color = Setting::whereName('landing_header_bg_color')->first();
        
        
                view()->share('landing_header_bg_color', $landing_header_bg_color);
            
            
            }

            public function landingtextcolor() 
            {
            
                $landing_header_text_color = Setting::whereName('landing_header_text_color')->first();
        
        
                view()->share('landing_header_text_color', $landing_header_text_color);
            
            
            }

            public function landingacttextcolor() 
            {
            
                $landing_header_active_text_color = Setting::whereName('landing_header_active_text_color')->first();
        
        
                view()->share('landing_header_active_text_color', $landing_header_active_text_color);
            
            
            }

            public function landingfooterbgcolor() 
            {
            
                $landing_footer_bg_color = Setting::whereName('landing_footer_bg_color')->first();
        
        
                view()->share('landing_footer_bg_color', $landing_footer_bg_color);
            
            
            }

            public function landingfootertextcolor() 
            {
            
                $landing_footer_text_color = Setting::whereName('landing_footer_text_color')->first();
        
        
                view()->share('landing_footer_text_color', $landing_footer_text_color);
            
            
            }                    
            public function logo() 
        {
        
                $logo = Setting::whereName('logo')->first();
        
        
             view()->share('logo', $logo);
        
        
            }
//singlelandingpage

              public function singlelandingbgcolor() 
            {
            
                $single_landing_header_bg_color = Setting::whereName('single_landing_header_bg_color')->first();
        
        
                view()->share('single_landing_header_bg_color', $single_landing_header_bg_color);
            
            
            }

            public function singlelandingtextcolor() 
            {
            
                $single_landing_header_text_color = Setting::whereName('single_landing_header_text_color')->first();
        
        
                view()->share('single_landing_header_text_color', $single_landing_header_text_color);
            
            
            }

            public function singlelandingacttextcolor() 
            {
            
                $single_landing_header_active_text_color = Setting::whereName('single_landing_header_active_text_color')->first();
        
        
                view()->share('single_landing_header_active_text_color', $single_landing_header_active_text_color);
            
            
            }

            public function singlelandingfooterbgcolor() 
            {
            
                $single_landing_footer_bg_color = Setting::whereName('single_landing_footer_bg_color')->first();
        
        
                view()->share('single_landing_footer_bg_color', $single_landing_footer_bg_color);
            
            
            }

            public function singlelandingfootertextcolor() 
            {
            
                $single_landing_footer_text_color = Setting::whereName('single_landing_footer_text_color')->first();
        
        
                view()->share('single_landing_footer_text_color', $single_landing_footer_text_color);
            
            
            }

            public function favicon() 
        {
        
                $favicon = Setting::whereName('favicon')->first();
        
        
             view()->share('favicon', $favicon);
        
        
            }
            public function loginbg() 
        {
        
                $loginbg = Setting::whereName('loginbg')->first();
        
        
             view()->share('loginbg', $loginbg);
        
        
            }

            public function owner_loginbg() 
            {
            
                    $owner_loginbg = Setting::whereName('owner_loginbg')->first();
            
            
                 view()->share('owner_loginbg', $owner_loginbg);
            
            
                }
            public function admin_url() 
            {
                $admin_url = Setting::whereName('admin_login')->first();
                view()->share('admin_url', $admin_url);
            }

            public function bannerbgcolor() 
            {
                $banner_bg_color = Setting::whereName('banner_bg_color')->first();
                view()->share('banner_bg_color', $banner_bg_color);
            }

            public function bannertextcolor(){
                $banner_title_color = Setting::whereName('banner_title_color')->first();
                view()->share('banner_title_color', $banner_title_color);
            }

            public function bannerdescriptioncolor(){
                $banner_description_color = Setting::whereName('banner_description_color')->first();
                view()->share('banner_description_color', $banner_description_color);
            }
           
            public function bannerbuttoncolor(){
                $banner_button_color = Setting::whereName('banner_button_color')->first();
                view()->share('banner_button_color', $banner_button_color);
            }
    
}
