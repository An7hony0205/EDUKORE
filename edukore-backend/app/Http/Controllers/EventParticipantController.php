<?php

namespace App\Http\Controllers;

use App\Models\EventParticipant;
use App\Models\Obligation;
use Illuminate\Http\Request;

class EventParticipantController extends Controller
{
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:PENDING,PRESENT,ABSENT,EXCUSED',
            'notes' => 'nullable|string'
        ]);

        $participant = EventParticipant::findOrFail($id);
        
        // Ensure tenant matches
        if ($participant->event->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $participant->update($validated);
        
        // If absent, we could create an obligation automatically
        if ($validated['status'] === 'ABSENT') {
            Obligation::firstOrCreate([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => $participant->user_id,
                'title' => 'Inasistencia a ' . $participant->event->title,
            ], [
                'description' => 'Falta no justificada al evento de comunidad.',
                'status' => 'PENDING'
            ]);
        }

        return response()->json($participant->load('user'));
    }
}
