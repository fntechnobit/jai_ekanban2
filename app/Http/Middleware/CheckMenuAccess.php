<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\GroupMenuAccess;
use Illuminate\Support\Facades\Auth;

class CheckMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menuCode, string $permission = 'can_read'): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(401, 'Unauthorized');
        }

        // Get user's group
        $groupId = $user->group_id;
        
        // Get menu access permissions
        $access = GroupMenuAccess::whereHas('menu', function($query) use ($menuCode) {
            $query->where('code', $menuCode);
        })->where('group_id', $groupId)
          ->where($permission, true)
          ->first();

        if (!$access) {
            abort(403, 'You do not have permission to access this resource');
        }

        return $next($request);
    }
}
