<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Master\BannerImage;
use Illuminate\Http\Request;
use App\Base\Services\ImageUploader\ImageUploader;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Models\Master\MobileAppSetting;
use App\Models\Admin\BannerImageModel;
use Illuminate\Support\Facades\Validator;

class BannerImageController extends Controller
{

    protected $imageUploader;
    protected $bannerimage;

    public function __construct(ImageUploaderContract $imageUploader,BannerImage $bannerimage)
    {
        $this->imageUploader = $imageUploader;
        
        $this->bannerimage = $bannerimage;
    }
    
    public function index() {

        return Inertia::render('pages/banner_image/index');
        
    }

    public function list(QueryFilterContract $queryFilter ,Request $request)
    {
        $query = BannerImage::query()->paginate();
        // dd($query);
        // $results = $queryFilter->builder($query)->paginate();
        // dd($results);

        return response()->json([
            'results' => $query->items(),
            'paginator' => $query,
        ]);
    }

    public function create() {
        return Inertia::render('pages/banner_image/create');
    }

    public function store(Request $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        
        $request->validate([
            'image'  => 'required',
            'image_url' => 'nullable',
        ]);

        $created_params = $request->all();
        $created_params['active'] = true;

        if ($uploadedFile = $request->file('image')) {
            $created_params['image'] = $this->imageUploader->file($uploadedFile)
                ->saveBannerImage();
        }
        BannerImage::create($created_params);

        return response()->json([
            'successMessage' => 'Banner Image created successfully.'
        ], 201);
    } 

    public function edit($id)
    {
        $bannerimage = BannerImage::findorfail($id);
        return Inertia::render(
            'pages/banner_image/create',
            ['bannerimage' => $bannerimage,]
        );
    }

    public function update(Request $request, BannerImage $bannerimage)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }

        $request->validate([
            'image'  => 'required',
            'image_url' => 'nullable',
        ]);

        // $updated_params = $request->only(['image']);
        // $updated_params['active'] = true;

        // if ($uploadedFile = $request->file('image')) {
        //     $updated_params['image'] = $this->imageUploader->file($uploadedFile)
        //         ->saveBannerImage();
        // }

        // $bannerimage->update($updated_params);
       
        $updated_params = [
            'active' => true,
            'image_url' => $request->image_url,
        ];

        if ($request->hasFile('image')) {
            $updated_params['image'] = $this->imageUploader
                ->file($request->file('image'))
                ->saveBannerImage();
        }

        $bannerimage->update($updated_params);

        return response()->json([
            'successMessage' => 'Banner Image updated successfully.',
            'bannerimage' => $bannerimage,
        ], 201);

    }

    public function destroy(BannerImage $bannerimage)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        $bannerimage->delete();

        return response()->json([
            'successMessage' => 'Banner Image deleted successfully',
        ]);
    } 

    public function updateStatus(Request $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        BannerImage::where('id', $request->id)->update(['active'=> $request->status]);

        return response()->json([
            'successMessage' => 'Banner Image status updated successfully',
        ]);

    }
    public function bannerIndex(){
        return Inertia::render('pages/banners/index');
    }

    public function bannerList(QueryFilterContract $queryFilter ,Request $request)
    {
        $query = BannerImageModel::with('appmodule')->orderBy('created_at','DESC')->paginate();
        // $results = $queryFilter->builder($query)->paginate();
        //  dd($results);

        return response()->json([
            'results' => $query->items(),
            'paginator' => $query,
        ]);

    }
    
    public function bannerCreate()
    {
        $app_module = MobileAppSetting::where('active', true)->get();

        return Inertia::render('pages/banners/create', [
            'app_modules' => $app_module
        ]);
    }
       
    public function bannerStore( Request $request){
        $request->validate([
            'image'  => 'required',
            'title' => 'required',
            'appmodule_id' => 'nullable',
            'imageurl' =>'nullable',
            'description' => 'required',
            'button_name' => 'nullable',
            'enable_banner_button' =>'nullable',
            'banner_bg_color' => 'nullable',
            'banner_title_color' => 'nullable',
            'banner_description_color' => 'nullable',
            'banner_button_color' => 'nullable',
            'banner_button_text_color' => 'nullable',
            'banner_button_text_color' => 'nullable',
        ]);

        $created_params = [
            'title' => $request->title,
            'bannertype' => $request->bannertype,
            'imageurl' => $request->imageurl,
            'description' => $request->description,
            'appmodule_id' => $request->appmodule_id,
            'button_name' => $request->button_name,
            'enable_banner_button' => $request->enable_banner_button,
            'banner_bg_color' => $request->banner_bg_color,
            'banner_title_color' => $request->banner_title_color,
            'banner_description_color' => $request->banner_description_color,
            'banner_button_color' => $request->banner_button_color,
            'banner_button_text_color' => $request->banner_button_text_color,
            'active' => true,
        ];

        if ($uploadedFile = $request->file('image')) {
            $created_params['image'] = $this->imageUploader->file($uploadedFile)
                ->saveBannerImage();
        }
        
        $banner = BannerImageModel::create($created_params);

        // 2️ save preview image
        if ($request->previewimage) {
            $this->saveBannerPreview($banner, $request->previewimage);
        }
        // dd($request->previewimage);   

        return response()->json([
            'successMessage' => 'Banner Image created successfully.'
        ], 201);
    }

    public function bannerEdit($id){

        $bannerimages = BannerImageModel::findorfail($id);
        $app_module = MobileAppSetting::where('active', true)->get();

        return Inertia::render('pages/banners/create', ['app_for'=>env('APP_FOR'),
            'app_modules' => $app_module,
            'bannerimages' => $bannerimages,
        ]);

    }
    public function bannerUpdate(request $request, BannerImageModel $bannerimage){
        $request->validate([
            'image'  => 'required',
            'title' => 'required',
            'appmodule_id' => 'nullable',
            'imageurl' =>'nullable',
            'description' => 'required',
            'button_name' => 'nullable',
            'enable_banner_button' =>'nullable',
            'banner_bg_color' => 'nullable',
            'banner_title_color' => 'nullable',
            'banner_description_color' => 'nullable',
            'banner_button_color' => 'nullable',
        ]);

        $updated_params = [
            'title' => $request->title,
            'bannertype' => $request->bannertype,
            'imageurl' => $request->imageurl,
            'description' => $request->description,
            'appmodule_id' => $request->appmodule_id,
            'button_name' => $request->button_name,
            'enable_banner_button' => $request->enable_banner_button,
            'banner_bg_color' => $request->banner_bg_color,
            'banner_title_color' => $request->banner_title_color,
            'banner_description_color' => $request->banner_description_color,
            'banner_button_color' => $request->banner_button_color,
            'banner_button_text_color' => $request->banner_button_text_color,
            'active' => true,
        ];
        // dd($request->enable_banner_button);

       if ($request->hasFile('image')) {
            $updated_params['image'] = $this->imageUploader
                ->file($request->file('image'))
                ->saveBannerImage();
        }

        if ($request->previewimage) {
            $this->saveBannerPreview($bannerimage, $request->previewimage);
        }

       $bannerimage->update( $updated_params);
        // dd($created_params);    

        return response()->json([
            'successMessage' => 'Banner Image updated successfully.',
            'bannerimage' => $bannerimage
        ], 201);
    }

    public function bannerDestroy(BannerImageModel $bannerimage)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        $bannerimage->delete();

        return response()->json([
            'successMessage' => 'Banner Image deleted successfully',
        ]);
    } 

    public function bannerUpdateStatus(Request $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        BannerImageModel::where('id', $request->id)->update(['active'=> $request->status]);

        return response()->json([
            'successMessage' => 'Banner Image status updated successfully',
        ]);

    }
    // color settings endpoints kept for backward compatibility.
    // Banner color persistence is now handled per-banner in `bannersimages`.
    public function getColorSettings()
    {
        return response()->json([
            'settings' => null,
            'app_for' => env('APP_FOR')
        ]);
    }

    public function updateColorSettings(Request $request)
    {
        return response()->json([
            'alertMessage' => 'Global banner color settings are disabled. Save colors in banner form.'
        ], 422);
    }

    private function saveBannerPreview(BannerImageModel $bannerimage, string $base64)
    {
        if (!$base64) {
            return;
        }

        // Remove base64 prefix
        $image = preg_replace('#^data:image/\w+;base64,#i', '', $base64);

        // Decode
        $image = base64_decode($image);

        // Generate filename
        $filename = 'preview_' . uniqid() . '.png';

        // Path
        $path = storage_path('app/public/uploads/banner/banner-preview/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        // Save image
        file_put_contents($path, $image);

        // Save filename in DB
        $bannerimage->update([
            'previewimage' => $filename
        ]);
    }
    
}
