<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::with('participants.user')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->get();

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'event_type' => 'required|in:FAENA,MEETING,OTHER',
            'status' => 'nullable|in:SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED'
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;
        if (!isset($validated['status'])) {
            $validated['status'] = 'SCHEDULED';
        }

        $event = Event::create($validated);

        // Optionally, pre-populate event_participants with all users with 'Parent' role
        $parents = \App\Models\User::where('tenant_id', auth()->user()->tenant_id)
            ->whereHas('roles', function($q) {
                $q->where('name', 'Parent');
            })->get();
            
        foreach($parents as $parent) {
            $event->participants()->create([
                'user_id' => $parent->id,
                'status' => 'PENDING'
            ]);
        }

        return response()->json($event->load('participants.user'), 201);
    }
    
    public function show(Event $event)
    {
        if ($event->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        return response()->json($event->load('participants.user'));
    }

    public function updateStatus(Request $request, Event $event)
    {
        if ($event->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'status' => 'required|in:SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED'
        ]);

        $event->update($validated);
        return response()->json($event);
    }
}
