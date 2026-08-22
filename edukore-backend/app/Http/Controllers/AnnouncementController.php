<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $role = auth()->user()->roles->first()->name ?? null;

        $query = Announcement::with('author:id,name')->where('tenant_id', $tenantId);

        if ($role !== 'Admin') {
            $query->where('is_published', true)
                  ->where(function ($q) use ($role) {
                      $q->whereNull('target_role')
                        ->orWhere('target_role', $role);
                  });
        }

        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_role' => 'nullable|string',
            'is_published' => 'boolean'
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['author_id'] = auth()->id();

        $announcement = Announcement::create($validated);
        return response()->json($announcement, 201);
    }
    
    public function update(Request $request, $id)
    {
        $announcement = Announcement::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $validated = $request->validate([
            'is_published' => 'boolean'
        ]);
        $announcement->update($validated);
        return response()->json($announcement);
    }

    public function destroy($id)
    {
        $announcement = Announcement::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $announcement->delete();
        return response()->json(null, 204);
    }
}
