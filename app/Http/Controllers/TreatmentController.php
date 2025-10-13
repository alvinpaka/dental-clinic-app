<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
class TreatmentController extends Controller
{
    public function index()
    {
        $treatments = Treatment::with(['patient:id,name,email', 'appointment', 'invoice'])->paginate(10);
        $patients = Patient::select('id', 'name', 'email')->get();
        
        $appointmentTypes = [
            'Dental Cleaning',
            'Tooth Extraction',
            'Root Canal',
            'Dental Filling',
            'Dental Crown',
            'Dental Bridge',
            'Dental Implant',
            'Teeth Whitening',
            'Orthodontic Treatment',
            'Periodontal Treatment',
            'Dental X-Ray',
            'Oral Surgery',
            'Emergency Dental Care',
            'Dental Consultation'
        ];
        
        return Inertia::render('Treatments/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'treatments' => $treatments, 
            'patients' => $patients,
            'appointmentTypes' => $appointmentTypes
        ]);
    }

    public function show(Treatment $treatment)
    {
        $treatment->load(['patient', 'appointment']);
        return Inertia::render('Treatments/Show', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'treatment' => $treatment
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'procedure' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'file' => 'nullable|image|mimes:jpeg,png|max:2048',
        ]);

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('treatments', 'public');
        }

        Treatment::create($validated);

        return redirect()->route('patients.show', $validated['patient_id'])->with('success', 'Treatment recorded.');
    }

    public function update(Request $request, Treatment $treatment)
    {
        // Prevent editing a treatment that already has an associated invoice
        if ($treatment->invoice()->exists()) {
            return redirect()->route('treatments.index')->with('error', 'This treatment has an invoice and cannot be edited.');
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'procedure' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'file' => 'nullable|image|mimes:jpeg,png|max:2048',
        ]);

        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($treatment->file_path) {
                Storage::disk('public')->delete($treatment->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('treatments', 'public');
        }

        $treatment->update($validated);

        return redirect()->route('treatments.index')->with('success', 'Treatment updated.');
    }

}