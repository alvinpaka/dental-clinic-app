<?php

namespace App\Http\Controllers;

use App\Models\MedicalHistory;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MedicalHistoryController extends Controller
{
    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);
        $history = $patient->medicalHistory;        
        return Inertia::render('Patients/MedicalHistory', [
            'auth' => [ 'user' => auth()->user() ],
            'patient' => $patient->only(['id','name','email','dob']),
            'history' => $history,
        ]);
    }

    public function upsert(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);
        $data = $request->validate([
            'conditions' => 'nullable|string',
            'medications' => 'nullable|string',
            'allergies' => 'nullable|string',
            'alerts' => 'nullable|string',
        ]);

        $history = $patient->medicalHistory;
        if (!$history) {
            $history = new MedicalHistory(['patient_id' => $patient->id]);
        }
        $history->fill($data);
        $history->last_reviewed_at = now();
        $history->reviewed_by = optional($request->user())->id;
        $history->save();

        // audit
        try {
            \App\Models\AuditLog::create([
                'user_id' => optional($request->user())->id,
                'action' => 'medical_history.upsert',
                'subject_type' => MedicalHistory::class,
                'subject_id' => $history->id,
                'metadata' => [ 'patient_id' => $patient->id ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('patients.medical-history.show', $patient)->with('success', 'Medical history saved.');
    }
}
