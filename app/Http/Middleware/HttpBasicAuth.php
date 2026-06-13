<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $envUser = config('crons.user');
        $envPassword = config('crons.password');

        if ($request->getUser() != $envUser || $request->getPassword() != $envPassword) {
            return response('Unauthorized', Response::HTTP_UNAUTHORIZED)->header('WWW-Authenticate', 'Basic');
        }

        return $next($request);
    }
}
