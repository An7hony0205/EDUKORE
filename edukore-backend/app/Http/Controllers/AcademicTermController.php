<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use Illuminate\Http\JsonResponse;

class AcademicTermController extends Controller
{
    public function index(): JsonResponse
    {
        $terms = AcademicTerm::where('is_active', true)
            ->orderBy('order_index')
            ->get();

        return response()->json(['data' => $terms]);
    }
}
