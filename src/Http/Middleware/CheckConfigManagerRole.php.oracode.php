<?php

namespace Ultra\UltraConfigManager\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * CheckConfigManagerRole
 *
 * This middleware ensures that the authenticated user has the appropriate
 * permission or role to access specific configuration-related routes.
 *
 * It supports Spatie permissions if enabled, with a fallback to custom role logic.
 */
class CheckConfigManagerRole
{
    /**
     * Handle the incoming request and authorize it based on permissions.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $permission  The permission string to check (e.g., 'view-config')
     * @return mixed
     */
/**
 * TODO: [UDP] Describe purpose of 'handle'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
    public function handle($request, Closure $next, $permission)
    {
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // If Spatie permissions are enabled and the user supports the method
        if (config('uconfig.use_spatie_permissions') && method_exists($user, 'hasPermissionTo')) {
            if (!$user->hasPermissionTo($permission)) {
                abort(403, 'You do not have permission to perform this action.');
            }
        } else {
            // Fallback to simple role-based check (e.g., 'role' column on User model)
            $requiredRole = match ($permission) {
                'view-config' => 'ConfigViewer',
                'create-config', 'update-config', 'delete-config' => 'ConfigManager',
                default => 'ConfigManager',
            };

            if ($user->role !== $requiredRole) {
                abort(403, 'You do not have permission to perform this action.');
            }
        }

        return $next($request);
    }
}
