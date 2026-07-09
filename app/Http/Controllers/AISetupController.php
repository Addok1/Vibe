<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\ThirdPartySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AISetupController extends Controller
{
    public function index() {
        $settings = ThirdPartySetting::where('module', 'ai-setup')->pluck('value', 'name')->toArray();
        return Inertia::render('pages/ai-setup/index', [
            'app_for'=>env('APP_FOR'),
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $settings = $request->only([
            'enable_open_ai_setup',
            'open_ai_api_key',
            'open_ai_organization_name',
        ]);

        // dd($settings);

        ThirdPartySetting::where('module', 'ai-setup')->delete(); // corrected delete command


        foreach ($settings as $key => $setting) 
        {
            // dd($setting);

            ThirdPartySetting::create(['name' => $key, 'value' => $setting, 'module' => 'ai-setup']);                 
        }

        // Update the .env file with the new settings
             $this->updateEnvFile($settings);
             
        return response()->json(['message' => 'Recaptcha  Destails updated successfully'], 201);

    }
    /**
 * Update the .env file with new settings.
 *
 * @param array $settings
 * @return void
 */
private function updateEnvFile(array $settings)
{
    // Get the path to the .env file
    $envPath = base_path('.env');

    // Check if the .env file exists
    if (file_exists($envPath)) {
        // Read the current content of the .env file
        $envContent = file_get_contents($envPath);

        // Update or add each setting in the .env file
        foreach ($settings as $key => $value) {
            $envKey = strtoupper($key); // Convert the key to uppercase to match the .env convention

            // Create a regex pattern to match the existing key-value pair
            $pattern = "/^{$envKey}=[^\r\n]*/m";

            // If the key exists, replace it; otherwise, append the new key-value pair
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$envKey}={$value}", $envContent);
            } else {
                $envContent .= "\n{$envKey}={$value}";
            }
        }

        // Write the updated content back to the .env file
        file_put_contents($envPath, $envContent);
    }
}


public function generateAiLandingText(Request $request)
    {
        $type = $request->type;
        $value = $request->value;

         if ($type === 'about_lists' || 'req_lists' || 'vechile_req_lists'|| 'doc_req_lists' || 'about_lists') {
            $prompt = "Generate a list separated by commas (,) for the following content: {$value}.
            Do not use numbers, bullets, or any extra text.
            Return only comma-separated items.";
        } else {
            $prompt = "Generate {$type} based on this input: {$value}";
        }

        $api_key = env("OPEN_AI_API_KEY");
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $api_key,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4.1-mini',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ]);

        $data = $response->json();

        $text = $data['choices'][0]['message']['content'] ?? '';

        return response()->json([
            'text' => trim($text)
        ]);
    }

}
