<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Closure;

class AgentAddonChecking
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
     public function handle($request, Closure $next)
    {
        // Get addon status
        $agent_addons = Setting::where('name', 'dispatcher-addons')->value('value');

        // If disabled → block access
        if ($agent_addons != 1) {
            return abort(403, 'Agent addon is disabled.');
        }

        return $next($request);
    }
}
