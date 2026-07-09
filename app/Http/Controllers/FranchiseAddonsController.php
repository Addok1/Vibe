<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Master\BannerImage;
use Illuminate\Http\Request;
use App\Base\Services\ImageUploader\ImageUploader;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use ZipArchive;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Jobs\FranchiseZipFilesAddons;


class FranchiseAddonsController extends Controller
{
    public function index() {
        return Inertia::render('pages/franchise_addons/index');
    }
     public function verification_submit(Request $request)
    {    
            $format = check_code_format($request->purchase_code);

            if($format['success'])
            {
                $UpdateSettingcontract = app("update-service");
                $softwarecheck = $UpdateSettingcontract->franchisecodeVerify(); 
               return json_encode($softwarecheck);
            }
            else{
                return json_encode($format);
            } 
       

    }
    public function franchise_files_uploads(Request $request){
//  dd($request);
        try {
            $request->validate([
                'franchise_zip_file' => 'required|file|mimes:zip',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        $uploadedFile = $request->file('franchise_zip_file');
        $uploadPath = storage_path('app/public/');

        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Move ZIP file to temporary folder
        $zipFileName = $uploadedFile->getClientOriginalName();
        $uploadedFile->move($uploadPath, $zipFileName);
        $zipFilePath = $uploadPath . '/' . $zipFileName;

        FranchiseZipFilesAddons::dispatch($zipFileName, $zipFilePath);

         return response()->json([
                 'success' => true,
                'message' => 'Module files extracted and stored successfully!',
            ], 201);

    }
}