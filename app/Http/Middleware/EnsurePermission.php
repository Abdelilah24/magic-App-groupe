<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Vérifie que l'utilisateur connecté possède la permission requise.
     *
     * Usage dans les routes :
     *   ->middleware('permission:reservations.accept')
     *   ->middleware('permission:reservations.accept,reservations.refuse')  // au moins l'une
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // Le super_admin passe toujours (géré dans hasPermission(), mais doublon de sécurité)
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Vérifier qu'au moins une permission est satisfaite
        foreach ($permissions as $permission) {
            if ($user->hasPermission(trim($permission))) {
                return $next($request);
            }
        }

        // Accès refusé
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        abort(403, 'Vous n\'avez pas la permission d\'effectuer cette action.');
    }
}
