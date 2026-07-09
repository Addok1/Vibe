<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use App\Models\Setting;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Guests trying to access customer web booking should see customer login
        // (never return 'login' or 'login/' to avoid redirect loop with /login -> /create-booking)
        $path = $request->path();
        if ($path === 'create-booking') {
            $user_login = Setting::where('name', 'user_login')->pluck('value')->first();
            $segment = (is_string($user_login) && trim($user_login) !== '') ? trim($user_login) : 'user';
            return 'login/' . $segment;
        }

        $admin_url = Setting::where('name', 'admin_login')->pluck('value')->first();
        $admin_segment = (is_string($admin_url) && trim($admin_url) !== '') ? trim($admin_url) : 'admin';
        return 'login/' . $admin_segment;
    }
}
