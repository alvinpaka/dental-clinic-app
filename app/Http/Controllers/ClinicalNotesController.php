<?php

namespace App\Http\Controllers;

use App\Models\ClinicalNote;
use App\Models\ClinicalNoteTemplate;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ClinicalNotesController extends Controller
{
    public function index(Patient $patient)
    {
        $this->authorize('view', $patient);
        $notes = ClinicalNote::with(['author', 'signer'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();
        $templates = ClinicalNoteTemplate::where('active', true)->orderBy('name')->get(['id','name','subjective','objective','assessment','plan']);
        return Inertia::render('Patients/ClinicalNotes', [
            'auth' => ['user' => auth()->user()],
            'patient' => $patient->only(['id','name']),
            'notes' => $notes,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);
        $data = $request->validate([
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
        ]);
        $note = ClinicalNote::create([
            'patient_id' => $patient->id,
            'author_id' => optional($request->user())->id,
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'status' => 'draft',
        ]);
        try {
            \App\Models\AuditLog::create([
                'user_id' => optional($request->user())->id,
                'action' => 'clinical_note.create',
                'subject_type' => ClinicalNote::class,
                'subject_id' => $note->id,
                'metadata' => ['patient_id' => $patient->id],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
            ]);
        } catch (\Throwable $e) {}
        return redirect()->route('patients.notes.index', $patient)->with('success', 'Note saved');
    }

    public function update(Request $request, Patient $patient, ClinicalNote $note)
    {
        $this->authorize('update', $patient);
        if ($note->patient_id !== $patient->id) abort(404);
        if ($note->status === 'signed') {
            return back()->withErrors(['note' => 'Signed notes cannot be modified.']);
        }
        $data = $request->validate([
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
        ]);
        $note->update($data);
        return back()->with('success', 'Note updated');
    }

    public function sign(Request $request, Patient $patient, ClinicalNote $note)
    {
        $this->authorize('update', $patient);
        if ($note->patient_id !== $patient->id) abort(404);
        if ($note->status === 'signed') return back();
        $note->update([
            'status' => 'signed',
            'signed_by' => optional($request->user())->id,
            'signed_at' => now(),
        ]);
        try {
            \App\Models\AuditLog::create([
                'user_id' => optional($request->user())->id,
                'action' => 'clinical_note.sign',
                'subject_type' => ClinicalNote::class,
                'subject_id' => $note->id,
                'metadata' => ['patient_id' => $patient->id],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
            ]);
        } catch (\Throwable $e) {}
        return back()->with('success', 'Note signed');
    }

    public function pdf(Patient $patient, ClinicalNote $note)
    {
        $this->authorize('view', $patient);
        if ($note->patient_id !== $patient->id) abort(404);
        $pdf = Pdf::loadView('notes.pdf', [
            'patient' => $patient,
            'note' => $note,
        ])->setPaper('a4');
        return $pdf->download('clinical-note-'.$patient->id.'-'.$note->id.'.pdf');
    }
}
