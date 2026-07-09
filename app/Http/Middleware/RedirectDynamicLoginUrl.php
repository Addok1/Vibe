<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;

class RedirectDynamicLoginUrl
{
    public function handle($request, Closure $next)
    {
        $url = $request->path();

        if ($url !== 'login') {
            return $next($request);
        }

        // Send /login directly to customer login page to avoid redirect loop
        // (no /create-booking in the chain for guests)
        $user_login = Setting::where('name', 'user_login')->pluck('value')->first();
        $segment = (is_string($user_login) && trim($user_login) !== '') ? trim($user_login) : 'user';
        return redirect('login/' . $segment);
    }
}