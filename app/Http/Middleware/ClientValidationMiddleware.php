<?php

namespace App\Http\Middleware;

use App\Enums\UserRoleEnum;
use App\Enums\UserValidationEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClientValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = Auth::user();

        if($user->role === UserRoleEnum::Client->value &&
         $user->validation !== UserValidationEnum::Accepted->value){
            abort(403, 'Seu cadastro ainda não foi aceito.');
        }

        return $next($request);
    }
}
