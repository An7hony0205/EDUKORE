<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    /**
     * Display a listing of fees (for admins/billing dashboard).
     */
    public function index(Request $request)
    {
        $query = Fee::with(['student.user', 'feeType', 'discounts'])->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'fees' => $query->paginate(20),
            'students' => \App\Models\Student::with('user')->get(),
            'tenant' => auth()->user()->tenant
        ]);
    }

    /**
     * Store a newly created fee in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'category' => 'required|in:ACADEMIC,COMMUNITY',
        ]);

        $tenant = auth()->user()->tenant;

        if ($tenant->institution_type === 'PUBLIC' && $validated['category'] === 'ACADEMIC') {
            return response()->json([
                'message' => 'No se pueden crear cobros académicos (pensiones/matrículas) en instituciones de tipo PÚBLICO. Solo se permiten cobros comunitarios (ej: APAFA).'
            ], 403);
        }

        $feeData = $validated;
        $feeData['tenant_id'] = $tenant->id;
        $feeData['status'] = 'pending';
        // Mock default currency if missing
        $feeData['currency'] = 'PEN';

        $fee = Fee::create($feeData);

        return response()->json($fee->load('student.user'), 201);
    }

    /**
     * Display the specified fee.
     */
    public function show(Fee $fee)
    {
        $fee->load('student.user', 'payments.user', 'feeType', 'discounts');
        return response()->json($fee);
    }
}
