<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenant = $user->tenant;
        
        if (!$tenant) {
            return response()->json(['message' => 'Tenant no encontrado.'], 404);
        }

        $activeModules = $tenant->active_modules ?? [];

        // If the module is explicitly set to false, reject
        if (isset($activeModules[$module]) && $activeModules[$module] === false) {
            return response()->json([
                'message' => 'Este módulo está desactivado para su institución.'
            ], 403);
        }

        return $next($request);
    }
}
