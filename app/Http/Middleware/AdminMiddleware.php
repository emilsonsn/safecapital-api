<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $position = Auth::user()->role;

        if(isset($position)){
            switch($position){
                case 'Admin':
                    return $next($request);
                default:
                    return response()->json(['error' => 'Acesso não autorizado'], 403);
            }
        }
        
        return response()->json(['error' => 'Acesso não autorizado'], 403);
    }
}
