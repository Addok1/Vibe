<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\ThirdPartySetting;
use Illuminate\Http\Request;
use App\Models\Admin\VehicleType;
use App\Models\Admin\ServiceLocation;
use App\Models\Request\Request as RequestModel;
use App\Models\Request\RequestPlace;
use Carbon\Carbon;

class MapSettingController extends Controller
{
    public function index() 
    {
        $settings = ThirdPartySetting::where('module', 'map')->pluck('value', 'name')->toArray();

        //   $map_type = get_map_settings('map_type');
    // dd($map_key);


        return Inertia::render('pages/map_settings/index', [
            'app_for'=>env('APP_FOR'),
            'settings' => $settings,
        ]);

    }

    public function mapShow() 
    {
        $settings = ThirdPartySetting::where('module', 'map')->pluck('value', 'name')->toArray();

        return Inertia::render('pages/map_show/index', [
            'app_for' => env('APP_FOR'),
            'settings' => $settings,
        ]);
    }

    public function mapShowUpdate(Request $request)
    {
        $request->validate([
            'map_show' => 'required|in:classic_layout,morden_layout',
        ]);

        ThirdPartySetting::updateOrCreate(
            ['module' => 'map', 'name' => 'map_show'],
            ['value' => $request->input('map_show')]
        );

        return response()->json(['message' => 'Map show setting updated successfully'], 201);
    }

     public function osmIndex()
    {
        $settings = ThirdPartySetting::where('module', 'map')->pluck('value', 'name')->toArray();

        $settings['enable_mapbox'] = filter_var($settings['enable_mapbox'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_thunderforest'] = filter_var($settings['enable_thunderforest'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['enable_stadia'] = filter_var($settings['enable_stadia'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return Inertia::render('pages/osm_map_settings/index', [
            'app_for' => env('APP_FOR'),
            'settings' => $settings,
        ]);
    }

    public function osmUpdate(Request $request)
    {
        $request->validate([
            'enable_mapbox' => 'nullable|boolean',
            'enable_thunderforest' => 'nullable|boolean',
            'enable_stadia' => 'nullable|boolean',
            'mapbox_public_key' => 'nullable|string',
            'thunderforest_api_key' => 'nullable|string',
            'stadia_api_key' => 'nullable|string',
        ]);

        $enableMapbox = $request->boolean('enable_mapbox');
        $enableThunderforest = $request->boolean('enable_thunderforest');
        $enableStadia = $request->boolean('enable_stadia');

        if ($enableMapbox) {
            $enableThunderforest = false;
            $enableStadia = false;
        } elseif ($enableThunderforest) {
            $enableMapbox = false;
            $enableStadia = false;
        } elseif ($enableStadia) {
            $enableMapbox = false;
            $enableThunderforest = false;
        }

        $settings = [
            'enable_mapbox' => $enableMapbox ? '1' : '0',
            'mapbox_public_key' => $request->input('mapbox_public_key', ''),
            'enable_thunderforest' => $enableThunderforest ? '1' : '0',
            'thunderforest_api_key' => $request->input('thunderforest_api_key', ''),
            'enable_stadia' => $enableStadia ? '1' : '0',
            'stadia_api_key' => $request->input('stadia_api_key', ''),
        ];

        foreach ($settings as $key => $setting) {
            ThirdPartySetting::updateOrCreate(
                ['module' => 'map', 'name' => $key],
                ['value' => $setting]
            );
        }

        return response()->json(['message' => 'OSM map settings updated successfully'], 201);
    }
   
    public function update(Request $request) 
    {

        $settings = $request->only([
            'map_type',
            // 'enable_vase_map',
            'google_map_key_for_distance_matrix',
            // 'google_sheet_id',
            'google_map_key',]);
        
        foreach ($settings as $key => $setting) 
        {
            ThirdPartySetting::where('name' , $key )->update(['value' => $setting,'module'=>'map']);
        }
  
    
        return response()->json(['message' => 'Map  Details updated successfully'], 201);
    }    
    public function heatmap(Request $request) 
    {

        $map_key = get_map_settings('google_map_key');

        // dd($map_key);

        // Calculate the date one week ago
        $oneWeekAgo = Carbon::now()->subWeek();

        $requestData = RequestPlace::whereBetween('created_at', [$oneWeekAgo, Carbon::now()])
            ->whereHas('requestDetail',function($locationQuery){
                $locationQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));
            })->get();

                // dd($requestData);
        $map_type = get_map_settings('map_type');

        if($map_type=="open_street_map")
        {
        return Inertia::render('pages/map/openheatmap',[
        'default_lat'=>get_settings('default_latitude'),'default_lng'=>get_settings('default_longitude'),
        'requestData'=>$requestData, 'map_key'=>$map_key]);
        }else{
            return Inertia::render('pages/map/heatmap',[
                'default_lat'=>get_settings('default_latitude'),'default_lng'=>get_settings('default_longitude'),
                'requestData'=>$requestData, 'map_key'=>$map_key]);    
        }
    }

    public function godseye() 
    {

        $service_location = ServiceLocation::where('active', true)
            ->whereIn('id',get_user_location_ids(auth()->user()))
            ->get(['id', 'name']);
        $vehicle_type = VehicleType::where('active', true)->get(['id', 'name']);

        $map_key = get_map_settings('google_map_key');
        
        // dd($vehicle_type);


        $firebaseSettings = [
            'firebase_api_key' => get_firebase_settings('firebase_api_key'),
            'firebase_auth_domain' => get_firebase_settings('firebase_auth_domain'),
            'firebase_database_url' => get_firebase_settings('firebase_database_url'),
            'firebase_project_id' => get_firebase_settings('firebase_project_id'),
            'firebase_storage_bucket' => get_firebase_settings('firebase_storage_bucket'),
            'firebase_messaging_sender_id' => get_firebase_settings('firebase_messaging_sender_id'),
            'firebase_app_id' => get_firebase_settings('firebase_app_id'),
        ];

          $map_type = get_map_settings('map_type');
       
          if($map_type=="open_street_map")
          {
            return Inertia::render('pages/map/godseye-open',['firebaseSettings'=>$firebaseSettings,
            'app_for' => env('APP_FOR'),
            'default_lat'=>get_settings('default_latitude'),'default_lng'=>get_settings('default_longitude'),
            'service_location'=>$service_location,'vehicle_type'=>$vehicle_type]);    
          }else{
            
            $default_location = (object)[
                "lat"=> (float) get_settings('default_latitude'),
                "lng"=> (float) get_settings('default_longitude'),
            ];
            return Inertia::render('pages/map/godseye',[
                'firebaseSettings'=>$firebaseSettings,
                'app_for' => env('APP_FOR'),
                'baseUrl'=>route('landing.index'),'default_location'=>$default_location,
                'service_location'=>$service_location,'vehicle_type'=>$vehicle_type,'map_key'=>$map_key
            ]);
          }


    }


}
