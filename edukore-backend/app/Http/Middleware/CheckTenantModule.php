<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantModule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant || !$tenant->active_modules || !($tenant->active_modules[$module] ?? false)) {
            return response()->json([
                'message' => "El módulo '{$module}' no está activado para tu institución. Por favor, contacta a soporte para actualizar tu plan."
            ], 403);
        }

        return $next($request);
    }
}
