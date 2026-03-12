<?php namespace Eprog\Manager\Classes;

use Closure;
use Laravel\Sanctum\PersonalAccessToken;
use RainLab\User\Models\User;

class BackendApiMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'No token'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || $accessToken->tokenable_type !== User::class) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }


        $request->attributes->set('user', $accessToken->tokenable);

        return $next($request);
    }
}
