<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $is_admin = auth()->user()->is_admin;
        $is_block = auth()->user()->is_block;
        if($is_admin || !$is_block) return $next($request);
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
