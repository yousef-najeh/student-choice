<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    /**
     * Handle an incoming request. Blocks any authenticated request from a banned user,
     * revoking the token they used so it can no longer be reused.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            $token = $user->currentAccessToken();
            if ($token) {
                $token->delete();
            }

            return response()->json([
                'message' => 'Your account has been banned.',
                'reason' => $user->banned_reason,
            ], 403);
        }

        return $next($request);
    }
}
