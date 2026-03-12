<?php namespace Eprog\Manager\Classes;

use Closure;
use Response;
use BackendAuth;

class BackendAuthMiddleware
{
    public function handle($request, Closure $next)
    {

        if (!BackendAuth::check()) {
            return Response::make('Forbidden', 403);
        }

        return $next($request);
    }
}
