<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Closure;

class FranchiseAddonChecking
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
     public function handle($request, Closure $next)
    {
        // Get addon status
        $franchise_addons = Setting::where('name', 'franchise-addons')->value('value');

        // If disabled → block access
        if ($franchise_addons != 1) {
            return abort(403, 'Franchise addon is disabled.');
        }

        return $next($request);
    }
}
