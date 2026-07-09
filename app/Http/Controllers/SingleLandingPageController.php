<?php

namespace App\Http\Controllers;

use App\Base\Filters\Master\CommonMasterFilter;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;
use App\Models\Admin\SingleLandingPage;
use App\Models\Admin\LandingHeader;
use Illuminate\Support\Facades\Validator;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use DB;
use Auth;
use Session;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Base\Services\ImageUploader\ImageUploader;
use Illuminate\Support\Str;
use App\Models\Admin\Setting;
use Illuminate\Support\Facades\Mail;
use App\Mail\Mailer\LandingContactEmail;
use App\Models\Admin\LandingEmail;
use Illuminate\Support\Facades\Http;
use App\Models\Languages;
use App\Models\Admin\SingleLandingHeader;
use App\Models\ThirdPartySetting;


class SingleLandingPageController extends BaseController
{
    protected $imageUploader;

    protected $singlelandingpage;

 

    public function __construct(SingleLandingPage $singlelandingpage, ImageUploaderContract $imageUploader)
    {
        $this->singlelandingpage = $singlelandingpage;
        $this->imageUploader = $imageUploader;
    }

    public function index()
    {
        return Inertia::render('pages/singlelandingpage/index');
    }


    // List of Vehicle Type
    public function list(QueryFilterContract $queryFilter, Request $request)
    {
        $query = SingleLandingPage::query();
//  dd($query);
        $results = $queryFilter->builder($query)->paginate();
// dd($results);
        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }
    public function create()
    {
        
        $settings = ThirdPartySetting::where('module', 'ai-setup')->pluck('value', 'name')->toArray();

        return Inertia::render('pages/singlelandingpage/create',['app_for'=>env('APP_FOR'),'settings'=>$settings['enable_open_ai_setup']]);
    }
    public function store(Request $request)
    {
         
        // Validate the incoming request
        $request->validate([
            'hero_para' => 'required',
            'hero_img_1' => 'required',
            'hero_img_2' => 'required',
            'hero_img_3' => 'required',
            'hero_img_4' => 'required',
            'hero_img_5' => 'required', 
            'adv_title' => 'required',
            'adv_para' => 'required',            
            'adv_box1_title' => 'required',
            'adv_box1_para' => 'required', 
            'adv_box1_img' => 'required', 
            'adv_box2_title' => 'required',
            'adv_box2_para' => 'required',
            'adv_box2_img' => 'required',
            'adv_box3_title' => 'required',
            'adv_box3_para' => 'required',
            'adv_box3_img' => 'required',
            'adv_box4_title' => 'required',
            'adv_box4_para' => 'required',
            'adv_box4_img'=>'required',
            'adv_box5_title' => 'required',
            'adv_box5_para' => 'required',
            'adv_box5_img' => 'required',
            'app_works_title' => 'required',
            'app_works_para' => 'required',
            'app_works_user_title' => 'required',
            'app_works_driver_title' => 'required',
            'user_box1_title' => 'required',
            'user_box1_para' => 'required',
            'user_box2_title' => 'required', 
            'user_box2_para' => 'required',
            'user_box3_title' => 'required',            
            'user_box3_para' => 'required',
            'user_box4_title' => 'required', 
            'user_box4_para' => 'required', 
            'driver_box1_title' => 'required',
            'driver_box1_para' => 'required',
            'driver_box2_title' => 'required',
            'driver_box2_para' => 'required',
            'driver_box3_title' => 'required',
            'driver_box3_para' => 'required',
            'driver_box4_title' => 'required',
            'driver_box4_para' => 'required',
            'app_user_img'=>'required',
            'app_driver_img' => 'required',
            'adv_box5_para' => 'required',
            'why_choose_title' => 'required',
            'why_choose_box1_title' => 'required',
            'why_choose_box1_para' => 'required',
            'why_choose_box2_title' => 'required',
            'why_choose_box2_para' => 'required',
            'why_choose_box3_title' => 'required',
            'why_choose_box3_para' => 'required',
            'why_choose_box4_title' => 'required', 
            'why_choose_box4_para' => 'required',
            'why_choose_img' => 'required',            
            'about_title_1' => 'required',
            'about_title_2' => 'required', 
            'about_img' => 'required', 
            'about_para' => 'required',
            'ceo_title_1' => 'required',
            'ceo_title_2' => 'required',
            'ceo_para' => 'required',
            'ceo_img' => 'required',
            'download_title' => 'required',
            'download_para' => 'required',
            'download_user_link_android' => 'required',
            'download_user_link_apple'=>'required',
            'download_driver_link_android' => 'required',
            'download_driver_link_apple' => 'required',
            'download_img1' => 'required',
            'download_img2' => 'required',
            'contact_heading' => 'required',
            'contact_para' => 'required',
            'contact_address_title' => 'required',
            'contact_address' => 'required',
            'contact_phone_title' => 'required',
            'contact_phone' => 'required',
            'contact_mail_title' => 'required',
            'contact_mail' => 'required',
            'contact_web_title' => 'required',
            'contact_web' => 'required',
            'form_name' => 'required',
            'form_mail' => 'required',
            'form_subject' => 'required',
            'form_message' => 'required',
            'form_btn' => 'required',
            'contact_img' => 'required',
            'locale' => 'required',
            'language' => 'required',
        ]);

        $created_params = $request->all();   

        // dd($created_params);
        // Handle single image uploads
        if ($uploadedFile = $request->file('hero_img_1')) {
            $created_params['hero_img_1'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('hero_img_2')) {
            $created_params['hero_img_2'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('hero_img_3')) {
            $created_params['hero_img_3'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('hero_img_4')) {
            $created_params['hero_img_4'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('hero_img_5')) {
            $created_params['hero_img_5'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
       if ($request->hasFile('adv_box1_img')) {
            foreach ($request->file('adv_box1_img') as $file) {
                $created_params['adv_box1_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
       if ($request->hasFile('adv_box2_img')) {
            foreach ($request->file('adv_box2_img') as $file) {
                $created_params['adv_box2_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
         if ($request->hasFile('adv_box3_img')) {
            foreach ($request->file('adv_box3_img') as $file) {
                $created_params['adv_box3_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
         if ($request->hasFile('adv_box4_img')) {
            foreach ($request->file('adv_box4_img') as $file) {
                $created_params['adv_box4_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
          if ($request->hasFile('adv_box5_img')) {
            foreach ($request->file('adv_box5_img') as $file) {
                $created_params['adv_box5_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
        if ($uploadedFile = $request->file('app_user_img')) {
            $created_params['app_user_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('app_driver_img')) {
            $created_params['app_driver_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('why_choose_img')) {
            $created_params['why_choose_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('about_img')) {
            $created_params['about_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('ceo_img')) {
            $created_params['ceo_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('download_img1')) {
            $created_params['download_img1'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('download_img2')) {
            $created_params['download_img2'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
       
        if ($request->hasFile('contact_img')) {
            foreach ($request->file('contact_img') as $file) {
                $created_params['contact_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
   
        SingleLandingPage::create($created_params);
    // dd($created_params);
    
        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Landing Page created successfully.'
        ], 201);
    }

    public function edit($id)
    {
        
        
        $settings = ThirdPartySetting::where('module', 'ai-setup')->pluck('value', 'name')->toArray();
        $singlelandingpage = SingleLandingPage::find($id);
        // dd($landingAbouts);
        return Inertia::render(
            'pages/singlelandingpage/create',
            ['singlelandingpage' => $singlelandingpage,'app_for'=>env('APP_FOR'),'settings'=>$settings['enable_open_ai_setup']]
        );
    }
    public function update(Request $request, SingleLandingPage $singlelandingpage)
    {     
         // Validate the incoming request
        $request->validate([
           'hero_para' => 'required',
            'hero_img_1' => 'required',
            'hero_img_2' => 'required',
            'hero_img_3' => 'required',
            'hero_img_4' => 'required',
            'hero_img_5' => 'required', 
            'adv_title' => 'required',
            'adv_para' => 'required',            
            'adv_box1_title' => 'required',
            'adv_box1_para' => 'required', 
            // 'adv_box1_img' => 'required', 
            'adv_box2_title' => 'required',
            'adv_box2_para' => 'required',
            // 'adv_box2_img' => 'required',
            'adv_box3_title' => 'required',
            'adv_box3_para' => 'required',
            // 'adv_box3_img' => 'required',
            'adv_box4_title' => 'required',
            'adv_box4_para' => 'required',
            // 'adv_box4_img'=>'required',
            'adv_box5_title' => 'required',
            'adv_box5_para' => 'required',
            // 'adv_box5_img' => 'required',
            'app_works_title' => 'required',
            'app_works_para' => 'required',
            'app_works_user_title' => 'required',
            'app_works_driver_title' => 'required',
            'user_box1_title' => 'required',
            'user_box1_para' => 'required',
            'user_box2_title' => 'required', 
            'user_box2_para' => 'required',
            'user_box3_title' => 'required',            
            'user_box3_para' => 'required',
            'user_box4_title' => 'required', 
            'user_box4_para' => 'required', 
            'driver_box1_title' => 'required',
            'driver_box1_para' => 'required',
            'driver_box2_title' => 'required',
            'driver_box2_para' => 'required',
            'driver_box3_title' => 'required',
            'driver_box3_para' => 'required',
            'driver_box4_title' => 'required',
            'driver_box4_para' => 'required',
            'app_user_img'=>'required',
            'app_driver_img' => 'required',
            'adv_box5_para' => 'required',
            'why_choose_title' => 'required',
            'why_choose_box1_title' => 'required',
            'why_choose_box1_para' => 'required',
            'why_choose_box2_title' => 'required',
            'why_choose_box2_para' => 'required',
            'why_choose_box3_title' => 'required',
            'why_choose_box3_para' => 'required',
            'why_choose_box4_title' => 'required', 
            'why_choose_box4_para' => 'required',
            'why_choose_img' => 'required',            
            'about_title_1' => 'required',
            'about_title_2' => 'required', 
            'about_img' => 'required', 
            'about_para' => 'required',
            'ceo_title_1' => 'required',
            'ceo_title_2' => 'required',
            'ceo_para' => 'required',
            'ceo_img' => 'required',
            'download_title' => 'required',
            'download_para' => 'required',
            'download_user_link_android' => 'required',
            'download_user_link_apple'=>'required',
            'download_driver_link_android' => 'required',
            'download_driver_link_apple' => 'required',
            'download_img1' => 'required',
            'download_img2' => 'required',
            'contact_heading' => 'required',
            'contact_para' => 'required',
            'contact_address_title' => 'required',
            'contact_address' => 'required',
            'contact_phone_title' => 'required',
            'contact_phone' => 'required',
            'contact_mail_title' => 'required',
            'contact_mail' => 'required',
            'contact_web_title' => 'required',
            'contact_web' => 'required',
            'form_name' => 'required',
            'form_mail' => 'required',
            'form_subject' => 'required',
            'form_message' => 'required',
            'form_btn' => 'required',
            // 'contact_img' => 'required',
            'locale' => 'required',
            'language' => 'required',
        ]);
        $updated_params = $request->all();
       
        if ($uploadedFile = $request->file('hero_img_1')) {
            $updated_params['hero_img_1'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('hero_img_2')) {
            $updated_params['hero_img_2'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('hero_img_3')) {
            $updated_params['hero_img_3'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('hero_img_4')) {
            $updated_params['hero_img_4'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('hero_img_5')) {
            $updated_params['hero_img_5'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
         if ($request->hasFile('adv_box1_img')) {
            foreach ($request->file('adv_box1_img') as $file) {
                $updated_params['adv_box1_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
       if ($request->hasFile('adv_box2_img')) {
            foreach ($request->file('adv_box2_img') as $file) {
                $updated_params['adv_box2_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
         if ($request->hasFile('adv_box3_img')) {
            foreach ($request->file('adv_box3_img') as $file) {
                $updated_params['adv_box3_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
         if ($request->hasFile('adv_box4_img')) {
            foreach ($request->file('adv_box4_img') as $file) {
                $updated_params['adv_box4_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
          if ($request->hasFile('adv_box5_img')) {
            foreach ($request->file('adv_box5_img') as $file) {
                $updated_params['adv_box5_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
        if ($uploadedFile = $request->file('app_user_img')) {
            $updated_params['app_user_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('app_driver_img')) {
            $updated_params['app_driver_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('why_choose_img')) {
            $updated_params['why_choose_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('about_img')) {
            $updated_params['about_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('ceo_img')) {
            $updated_params['ceo_img'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('download_img1')) {
            $updated_params['download_img1'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        if ($uploadedFile = $request->file('download_img2')) {
            $updated_params['download_img2'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingPage();
        }
        
        if ($request->hasFile('contact_img')) {
            foreach ($request->file('contact_img') as $file) {
                $updated_params['contact_img']  = $this->imageUploader->file($file)->saveSingleLandingPage();
            }
        }
        $singlelandingpage->update($updated_params);

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Landing Page updated successfully.',
            'singlelandingpage' => $singlelandingpage,
        ], 201);

    }
    public function destroy(SingleLandingPage $singlelandingpage)
    {
        $singlelandingpage->delete();

        return response()->json([
            'successMessage' => 'Landing Page deleted successfully',
        ]);
    } 

    private function getLocales()
    {
        // return LandingHeader::pluck('locale', 'id');
        return SingleLandingHeader::pluck('language', 'locale');
    }
public function contact_message(Request $request)
{
    //  dd($request->all());
   
    // Validate the incoming request
    $request->validate([
        'name' => 'required',
        'mail' => 'required',
        'subject' => 'required',
        'comments' => 'required',
        // 'recaptchaResponse' => 'required',
        'recaptchaResponse' => $request->has('recaptchaResponse') ? 'required' : 'nullable',
    ]);
   
    if ($request->has('recaptchaResponse')) {
        $recaptchaResponse = $request->recaptchaResponse;
        $secret_Key = config('services.recaptcha.secret');

        $recaptchaVerification = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => $secret_Key,
            'response' => $recaptchaResponse,
        ])->json();

        if (empty($recaptchaVerification['success'])) {
            return response()->json(['error' => 'reCAPTCHA validation failed.'], 422);
        }
    }
    // Create a new message
    $created_params = $request->only(['name', 'mail', 'subject', 'comments']);
    //  dd($created_params);
    LandingEmail::create($created_params);

    // Optionally, send the email
    Mail::to( env('MAIL_FROM_ADDRESS'))->send(new LandingContactEmail($created_params));

    // Return a response
    return response()->json([
        'successMessage' => 'Message created successfully.',
        'created_params' => $created_params,
    ], 201);
}

}
