<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'subdomain' => 'required|string',
        ]);

        // Find tenant
        $tenant = DB::table('tenants')->where('subdomain', $request->subdomain)->first();

        if (!$tenant || !$tenant->is_active) {
            return response()->json(['message' => 'Institución no encontrada o inactiva.'], 404);
        }

        $user = User::where('tenant_id', $tenant->id)
                    ->where('email', $request->email)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Formatear el rol para mantener la compatibilidad con el frontend
        $user->load('tenant');
        $roleName = $user->getRoleNames()->first();
        $userData = $user->toArray();
        $userData['role'] = ['name' => $roleName];

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $userData
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada exitosamente.']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('tenant');
        $roleName = $user->getRoleNames()->first();
        $userData = $user->toArray();
        $userData['role'] = ['name' => $roleName];
        
        return response()->json($userData);
    }
}
