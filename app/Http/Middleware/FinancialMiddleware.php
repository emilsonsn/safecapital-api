<?php

namespace App\Http\Middleware;

use App\Enums\UserRoleEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FinancialMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = Auth::user()?->role;

        if (! in_array($role, [UserRoleEnum::Admin->value, UserRoleEnum::Manager->value], true)) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        return $next($request);
    }
}
