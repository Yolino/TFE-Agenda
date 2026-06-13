<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasPersonalAgenda
{
    /**
     * Les directeurs n'ont pas d'agenda personnel (planning, congés,
     * justificatifs) : on les renvoie vers le planning global.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_directeur()) {
            return redirect()->route('planning')
                ->with('error', "Cette section n'est pas accessible aux directeurs.");
        }

        return $next($request);
    }
}
