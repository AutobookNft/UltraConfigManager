<?php

namespace Ultra\UltraConfigManager\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckConfigManagerRole
{
    public function handle($request, Closure $next, $permission)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Verifica se l'opzione 'use_spatie_permissions' è attiva
        if (config('uconfig.use_spatie_permissions') && method_exists($user, 'hasPermissionTo')) {
            // Usa il metodo di Spatie per controllare il permesso
            if (!$user->hasPermissionTo($permission)) {
                abort(403, 'Non hai i permessi per eseguire questa azione.');
            }
        } else {
            // Usa un controllo personalizzato (fallback)
            // Ad esempio, verifica un campo 'role' nel modello User
            $requiredRole = match ($permission) {
                'view-config' => 'ConfigViewer',
                'create-config', 'update-config', 'delete-config' => 'ConfigManager',
                default => 'ConfigManager',
            };
            if ($user->role !== $requiredRole) {
                abort(403, 'Non hai i permessi per eseguire questa azione.');
            }
        }

        return $next($request);
    }
}