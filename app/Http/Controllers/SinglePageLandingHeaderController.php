<?php

namespace App\Http\Controllers;

use App\Base\Filters\Master\CommonMasterFilter;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;
use App\Models\Admin\SingleLandingHeader;
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

class SinglePageLandingHeaderController extends BaseController
{
    protected $imageUploader;

    protected $singlelandingHeader;

 

    public function __construct(SingleLandingHeader $singlelandingHeader, ImageUploaderContract $imageUploader)
    {
        $this->singlelandingHeader = $singlelandingHeader;
        $this->imageUploader = $imageUploader;
    }

    public function home()
    {
        return Inertia::render('pages/singlelandingpage/header_footer/index');
    }


    // List of Vehicle Type
    public function list(QueryFilterContract $queryFilter, Request $request)
    {
        $query = SingleLandingHeader::query();
// dd("ssss");
        $results = $queryFilter->builder($query)->paginate();

        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }
    public function create()
    {

        return Inertia::render('pages/singlelandingpage/header_footer/create',['app_for'=>env('APP_FOR')]);
    }
    public function store(Request $request)
    {
         // Validate the incoming request
         $request->validate([
            'header_logo'=> 'required',
            'home'=> 'required',
            'aboutus'=> 'required',
            'apps'=> 'required',
            'contact'=> 'required',
            'book_now_btn'=> 'required',
            'footer_logo'=> 'required',
            'footer_para'=> 'required',
            'quick_links'=> 'required',
            'compliance'=> 'required',
            'privacy'=> 'required',
            'terms'=> 'required',
            'dmv'=> 'required',
            'user_app'=> 'required',
            'user_play'=> 'required',
            'user_play_link'=> 'required',
            'user_apple'=> 'required',
            'user_apple_link'=> 'required',
            'driver_app'=> 'required',
            'driver_play'=> 'required',
            'driver_play_link'=> 'required',
            'driver_apple'=> 'required',
            'driver_apple_link'=> 'required',
            'copy_rights'=> 'required',
            'fb_link'=> 'required',
            'linkdin_link'=> 'required',
            'x_link'=> 'required',
            'insta_link'=> 'required',
            'locale'=> 'required',
            'language'=> 'required',
        ]);

        $created_params = $request->all();

        if ($uploadedFile = $request->file('header_logo')) {
            $created_params['header_logo'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingHeaderImage();
        }
        if ($uploadedFile = $request->file('footer_logo')) {
            $created_params['footer_logo'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingHeaderImage();
        }

        // $landingHeader->create($created_params);

        SingleLandingHeader::create($created_params);

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Landing Header created successfully.'
        ], 201);
    }
    public function edit($id)
    {

        $singlelandingHeader = SingleLandingHeader::find($id);
        return Inertia::render(
            'pages/singlelandingpage/header_footer/create',
            ['singlelandingHeader' => $singlelandingHeader,'app_for'=>env('APP_FOR'),]
        );
    }
    public function update(Request $request, SingleLandingHeader $singlelandingHeader)
    {

         // Validate the incoming request
         $request->validate([
            'header_logo'=> 'required',
            'home'=> 'required',
            'aboutus'=> 'required',
            'apps'=> 'required',
            'contact'=> 'required',
            'book_now_btn'=> 'required',
            'footer_logo'=> 'required',
            'footer_para'=> 'required',
            'quick_links'=> 'required',
            'compliance'=> 'required',
            'privacy'=> 'required',
            'terms'=> 'required',
            'dmv'=> 'required',
            'user_app'=> 'required',
            'user_play'=> 'required',
            'user_play_link'=> 'required',
            'user_apple'=> 'required',
            'user_apple_link'=> 'required',
            'driver_app'=> 'required',
            'driver_play'=> 'required',
            'driver_play_link'=> 'required',
            'driver_apple'=> 'required',
            'driver_apple_link'=> 'required',
            'copy_rights'=> 'required',
            'fb_link'=> 'required',
            'linkdin_link'=> 'required',
            'x_link'=> 'required',
            'insta_link'=> 'required',
            'locale'=> 'required',
            'language'=> 'required',
        ]);

        $updated_params = $request->all();

        if ($uploadedFile = $request->file('header_logo')) {
            $updated_params['header_logo'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingHeaderImage();
        }
        if ($uploadedFile = $request->file('footer_logo')) {
            $updated_params['footer_logo'] = $this->imageUploader->file($uploadedFile)
                ->saveSingleLandingHeaderImage();
        }


        $singlelandingHeader->update($updated_params);

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Header Footer updated successfully.',
            'singlelandingHeader' => $singlelandingHeader,
        ], 201);

    }
    public function destroy(SingleLandingHeader $singlelandingHeader)
    {
        $singlelandingHeader->delete();

        return response()->json([
            'successMessage' => 'Header Footer deleted successfully',
        ]);
    } 

   

    // public function headerpage(Request $request)
    // {
    //     $locale = $request->query('locale', 'en');
    //     $language = $request->query('language', 'English');

    //     $headerData = LandingHeader::first();

    //     return Inertia::render('pages/landing/layout/landingheader', [
    //         'headerData' => $headerData,
    //     ]);
    // }
    

    public function index(Request $request)
    {
        $lang = $request->query('locale', 'en'); 
        // Default to 'en' if no lang is provided
        return response()->json(SingleLandingHeader::where('locale', $lang)->get());
    }
    


    public function getColorSettings()
{
    $settings = Setting::where('category', 'general')->get()->pluck('value', 'name');
    return response()->json(['settings' => $settings,'app_for'=>env('APP_FOR')]);
}

public function updateColorSettings(Request $request)
{
    $validated = $request->validate([
        'single_landing_header_bg_color' => 'string|nullable',
        'single_landing_header_text_color' => 'string|nullable',
        'single_landing_header_active_text_color' => 'string|nullable',
        'single_landing_footer_bg_color' => 'string|nullable',
        'single_landing_footer_text_color' => 'string|nullable',
    ]);

    foreach ($validated as $key => $value) {
        Setting::updateOrCreate(['name' => $key], ['value' => $value]);
    }
    \App\Models\Setting::clearSettingsCache();

    return response()->json(['successMessage' => 'Settings updated successfully.']);
}
 private function getLocales()
    {
        // return LandingHeader::pluck('locale', 'id');
        return SingleLandingHeader::pluck('language', 'locale');
    }

}
