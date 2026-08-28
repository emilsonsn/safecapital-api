<?php

namespace App\Http\Middleware;

use App\Enums\UserRoleEnum;
use App\Models\TermDocument;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClienteAcceptTermsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find(Auth::user()->id);

        $currentTerm = TermDocument::query()->latest('id')->first();

        if (! $currentTerm) {
            abort(503, 'Termo de uso não configurado.');
        }

        $acceptedCurrentTerm = $user->terms()
            ->where('terms_version', $currentTerm->version)
            ->exists();

        if ($user->role === UserRoleEnum::Client->value && ! $acceptedCurrentTerm) {
            abort(403, 'Aceite os termos para ter acesso a essa àrea.');
        }

        return $next($request);
    }
}
