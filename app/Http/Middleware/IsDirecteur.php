<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsDirecteur
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_directeur()) {
            return $next($request);
        }

        abort(403, "Accès réservé aux directeurs.");
    }
}
