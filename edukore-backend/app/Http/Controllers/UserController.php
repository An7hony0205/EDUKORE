<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $query = User::where('tenant_id', $tenantId);

        if ($request->has('role')) {
            $query->role($request->role);
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'email'])
        ]);
    }
}
