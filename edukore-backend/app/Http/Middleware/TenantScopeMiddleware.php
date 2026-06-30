<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // En una app real, determinaríamos el tenant por subdominio o por el usuario autenticado.
        // Aquí asumimos que si el usuario está autenticado, seteamos el RLS.
        if (auth()->check() && auth()->user()->tenant_id) {
            $tenantId = auth()->user()->tenant_id;
            \Illuminate\Support\Facades\DB::statement("SET LOCAL app.current_tenant_id = '{$tenantId}'");
        }

        return $next($request);
    }
}
