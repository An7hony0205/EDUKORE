<?php

namespace App\Http\Controllers;

use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantSettingController extends Controller
{
    /**
     * Display the tenant settings.
     */
    public function show(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $setting = TenantSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'timezone' => 'UTC',
                'grading_scale' => 'numeric_20',
                'tax_percentage' => 0,
                'currency_default' => 'USD'
            ]
        );

        return response()->json($setting);
    }

    /**
     * Update the tenant settings.
     */
    public function update(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $setting = TenantSetting::firstOrCreate(['tenant_id' => $tenantId]);

        $validated = $request->validate([
            'timezone' => 'nullable|string',
            'grading_scale' => 'nullable|string',
            'tax_percentage' => 'nullable|numeric|min:0',
            'currency_default' => 'nullable|string|size:3',
            'logo' => 'nullable|image|mimes:jpg,png,jpeg,svg|max:2048'
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($setting->logo_url) {
                Storage::disk('public')->delete($setting->logo_url);
            }
            $path = $request->file('logo')->store('tenant_logos', 'public');
            $validated['logo_url'] = $path;
        }

        $setting->update($validated);

        return response()->json($setting);
    }
}
