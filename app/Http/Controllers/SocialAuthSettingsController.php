<?php

namespace App\Http\Controllers;

use App\Models\ThirdPartySetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SocialAuthSettingsController extends Controller
{
    public function index()
    {
        $settings = ThirdPartySetting::where('module', 'social-auth')->pluck('value', 'name')->toArray();

        return Inertia::render('pages/social-auth-settings/index', [
            'app_for' => env('APP_FOR'),
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'enable_google_social_login' => 'nullable|in:0,1',
            'google_client_id' => 'required_if:enable_google_social_login,1|nullable|string',
            'google_client_secret' => 'required_if:enable_google_social_login,1|nullable|string',
            'enable_facebook_social_login' => 'nullable|in:0,1',
            'facebook_client_id' => 'required_if:enable_facebook_social_login,1|nullable|string',
            'facebook_client_secret' => 'required_if:enable_facebook_social_login,1|nullable|string',
            'enable_apple_social_login' => 'nullable|in:0,1',
            'apple_client_id' => 'required_if:enable_apple_social_login,1|nullable|string',
            'apple_team_id' => 'required_if:enable_apple_social_login,1|nullable|string',
            'apple_key_id' => 'required_if:enable_apple_social_login,1|nullable|string',
            'apple_private_key' => 'required_if:enable_apple_social_login,1|nullable|string',
        ]);

        $settings = $request->only([
            'enable_google_social_login',
            'google_client_id',
            'google_client_secret',
            'enable_facebook_social_login',
            'facebook_client_id',
            'facebook_client_secret',
            'enable_apple_social_login',
            'apple_client_id',
            'apple_team_id',
            'apple_key_id',
            'apple_private_key',
        ]);

        ThirdPartySetting::where('module', 'social-auth')->delete();

        foreach ($settings as $key => $setting) {
            ThirdPartySetting::create(['name' => $key, 'value' => $setting, 'module' => 'social-auth']);
        }

        // Keep parity with existing third-party settings controllers that sync to .env.
        $this->updateEnvFile([
            'GOOGLE_CLIENT_ID' => $settings['google_client_id'] ?? '',
            'GOOGLE_CLIENT_SECRET' => $settings['google_client_secret'] ?? '',
            'FACEBOOK_CLIENT_ID' => $settings['facebook_client_id'] ?? '',
            'FACEBOOK_CLIENT_SECRET' => $settings['facebook_client_secret'] ?? '',
            'APPLE_CLIENT_ID' => $settings['apple_client_id'] ?? '',
            'APPLE_TEAM_ID' => $settings['apple_team_id'] ?? '',
            'APPLE_KEY_ID' => $settings['apple_key_id'] ?? '',
            'APPLE_PRIVATE_KEY' => $settings['apple_private_key'] ?? '',
        ]);

        return response()->json(['message' => 'Social auth settings updated successfully'], 201);
    }

    private function updateEnvFile(array $settings)
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        foreach ($settings as $envKey => $value) {
            $pattern = "/^{$envKey}=[^\\r\\n]*/m";
            $line = $this->formatEnvLine($envKey, $value);
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $line, $envContent);
            } else {
                $envContent .= "\n" . $line;
            }
        }

        file_put_contents($envPath, $envContent);
    }

    private function formatEnvLine(string $key, $value): string
    {
        $value = (string) $value;

        if ($key === 'APPLE_PRIVATE_KEY') {
            $normalized = str_replace(["\r\n", "\r"], "\n", $value);
            $escaped = str_replace(['\\', '"', "\n"], ['\\\\', '\"', '\\n'], $normalized);

            return $key . '="' . $escaped . '"';
        }

        return $key . '=' . $value;
    }
}
