<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClinicSettingsController extends Controller
{
    public function edit(Clinic $clinic)
    {
        $this->authorize('update', $clinic);

        return Inertia::render('Clinics/Settings', [
            'clinic' => $clinic,
            'timezones' => \DateTimeZone::listIdentifiers(),
            'currencies' => [
                'USD' => 'US Dollar ($)',
                'EUR' => 'Euro (€)',
                'GBP' => 'British Pound (£)',
                'UGX' => 'Ugandan Shilling (USh)',
                'KES' => 'Kenyan Shilling (KSh)',
                'TZS' => 'Tanzanian Shilling (TSh)',
            ]
        ]);
    }

    public function update(Clinic $clinic, Request $request)
    {
        $this->authorize('update', $clinic);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clinics,email,' . $clinic->id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'timezone' => 'required|string|in:' . implode(',', \DateTimeZone::listIdentifiers()),
            'currency' => 'required|string|in:USD,EUR,GBP,UGX,KES,TZS',
            'business_hours' => 'required|array',
            'business_hours.*.day' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'business_hours.*.open' => 'required|string',
            'business_hours.*.close' => 'required|string',
            'business_hours.*.closed' => 'boolean',
            'settings.appointment_reminder_hours' => 'nullable|integer|min:1|max:168',
            'settings.payment_reminder_days' => 'nullable|integer|min:1|max:30',
            'settings.auto_invoice' => 'nullable|boolean',
            'settings.patient_portal' => 'nullable|boolean',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('clinics/logos', 'public');
        }

        $clinic->update($validated);

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    public function updateBranding(Clinic $clinic, Request $request)
    {
        $this->authorize('update', $clinic);

        $validated = $request->validate([
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($clinic->logo_path) {
                \Storage::disk('public')->delete($clinic->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('clinics/logos', 'public');
        }

        $clinic->update($validated);

        return redirect()->back()->with('success', 'Branding updated successfully.');
    }
}