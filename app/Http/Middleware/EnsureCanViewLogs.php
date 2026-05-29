<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint l'accès à la visualisation des logs aux rôles "Admin" et "Directeur".
 * Calqué sur le middleware IsAdmin existant ; s'appuie sur la méthode unique
 * User::canAccessLogs() (DRY).
 */
class EnsureCanViewLogs
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->canAccessLogs()) {
            return $next($request);
        }

        abort(403, "Accès réservé aux administrateurs et directeurs.");
    }
}
