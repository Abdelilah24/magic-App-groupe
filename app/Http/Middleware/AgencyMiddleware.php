<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AgencyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth('agency')->check()) {
            return redirect()->route('agency.login')
                ->with('error', 'Veuillez vous connecter pour accéder à votre espace.');
        }

        if (! auth('agency')->user()->isApproved()) {
            auth('agency')->logout();
            return redirect()->route('agency.login')
                ->with('error', 'Votre compte agence n\'est pas encore activé.');
        }

        return $next($request);
    }
}
