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

        return response()->json($query->paginate(20));
    }

    /**
     * Store a newly created fee in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'fee_type_id' => 'nullable|uuid|exists:fee_types,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'due_date' => 'required|date',
            'discount_ids' => 'nullable|array',
            'discount_ids.*' => 'uuid|exists:discounts,id'
        ]);

        $feeData = collect($validated)->except('discount_ids')->toArray();
        // Set discount_amount based on relations if applied? For now we just create the fee
        $feeData['discount_amount'] = 0; // Se recalcula en background o al añadir descuentos

        $fee = Fee::create($feeData);

        if (!empty($validated['discount_ids'])) {
            $fee->discounts()->syncWithPivotValues($validated['discount_ids'], ['applied_by' => auth()->id()]);
            // Recalculate discount_amount
            $totalDiscount = 0;
            foreach ($fee->discounts as $discount) {
                if ($discount->type === 'fixed') {
                    $totalDiscount += $discount->value;
                } else {
                    $totalDiscount += ($fee->amount * ($discount->value / 100));
                }
            }
            $fee->discount_amount = $totalDiscount;
            $fee->save();
        }

        return response()->json($fee->load('discounts', 'feeType'), 201);
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
