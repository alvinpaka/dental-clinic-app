<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Odontogram;
use App\Models\OdontogramTooth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OdontogramController extends Controller
{
    public function show(Patient $patient)
    {
        $odontogram = Odontogram::firstOrCreate(
            ['patient_id' => $patient->id],
            ['scheme' => 'FDI']
        );
        $odontogram->load('teeth');

        return Inertia::render('Patients/Odontogram', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'patient' => $patient,
            'odontogram' => $odontogram,
            'teeth' => $odontogram->teeth,
        ]);
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'scheme' => 'required|in:FDI,Universal',
            'note' => 'nullable|string',
            'teeth' => 'required|array',
            'teeth.*.tooth_code' => 'required|string',
            'teeth.*.status' => 'required|string',
            'teeth.*.note' => 'nullable|string',
        ]);

        $odontogram = Odontogram::updateOrCreate(
            ['patient_id' => $patient->id],
            ['scheme' => $data['scheme'], 'note' => $data['note'] ?? null]
        );

        // Sync teeth (simple replace for MVP)
        $existing = $odontogram->teeth()->pluck('id', 'tooth_code');
        // Delete removed
        $odontogram->teeth()->whereNotIn('tooth_code', collect($data['teeth'])->pluck('tooth_code'))->delete();
        // Upsert current set
        foreach ($data['teeth'] as $t) {
            OdontogramTooth::updateOrCreate(
                ['odontogram_id' => $odontogram->id, 'tooth_code' => $t['tooth_code']],
                ['status' => $t['status'], 'note' => $t['note'] ?? null]
            );
        }

        return redirect()->route('patients.odontogram.show', $patient)->with('success', 'Odontogram saved.');
    }
}
