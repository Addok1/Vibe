<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Admin\PackageType;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Base\Filters\Master\CommonMasterFilter;
use Illuminate\Support\Facades\Http;
use App\Models\ThirdPartySetting;

class RentalPackageTypeController extends Controller
{
    public function index() {
        return Inertia::render('pages/rental_package_types/index');
    }
    public function list(Request $request, QueryFilterContract $queryFilter)
    {
        $query = PackageType::query();
    
        // Apply the filters and paginate the results
        $results = $queryFilter->builder($query)
                              ->customFilter(new CommonMasterFilter)
                              ->paginate();
    
        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }
    
    public function create() {
        $settings = ThirdPartySetting::where('module', 'ai-setup')->pluck('value', 'name')->toArray();

        return Inertia::render('pages/rental_package_types/create',['settings'=>$settings['enable_open_ai_setup']]);
    }
    public function store(Request $request)
    {

        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
         // Validate the incoming request
         $created_params = $request->validate([
            'name' => 'required',
            'transport_type' => 'required',
            'description' => 'required',
            'short_description' => 'required',"",

        ]);

        // Create a new PackageType
        $packageType = PackageType::create($created_params);

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Package Type created successfully.',
            'packageType' => $packageType,
        ], 201);
    }
    public function edit($id)
    {
        $settings = ThirdPartySetting::where('module', 'ai-setup')->pluck('value', 'name')->toArray();

        $packageType = PackageType::find($id);
        return Inertia::render(
            'pages/rental_package_types/create',
            ['packageType' => $packageType,'settings'=>$settings['enable_open_ai_setup'],'app_for' =>env('APP_FOR') == 'demo']
        );
    }    
    public function update(Request $request, PackageType $packageType) 
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        // dd($packageType);
        // Create a new PackageType
        $packageType->update($request->all());

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Package Type created successfully.',
            'packageType' => $packageType,
        ], 201);    
    }
    public function destroy(PackageType $packageType)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        $packageType->delete();

        return response()->json([
            'successMessage' => 'Package Type deleted successfully',
        ]);
    }  
    public function updateStatus(Request $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        // dd($request->all());
        PackageType::where('id', $request->id)->update(['active'=> $request->status]);

        return response()->json([
            'successMessage' => 'Package Type status updated successfully',
        ]);


    }

    public function generateAiText(Request $request)
    {
        $prompt = $request->ai_text;
        $api_key = env("OPEN_AI_API_KEY");

        $type = $request->type;
        $shortInput = $request->short_description;
        $description = $request->description;

        // Dynamic Prompt based on type
        if ($type == 'short') {

        $prompt = "Short description: $shortInput

        Generate a short description:
        - Maximum 8 words
        - Catchy and concise
        - Do not explain, just a tagline

        Return only text.";

        } 
        else {

            $prompt = "Description: $description
            Short Description: $description

            Generate a detailed description:
            - 2 to 3 sentences
            - Minimum 25 words
            - Based on the short description
            - Clear and professional

            Return only text.";
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $api_key,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4.1-mini',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 120
        ]);

        $data = $response->json();

        $text = $data['choices'][0]['message']['content'] ?? '';

        return response()->json([
            'text' => trim($text)
        ]);
    }


}
