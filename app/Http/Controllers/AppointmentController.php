<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with(['patient', 'dentist'])->get()->map(fn ($apt) => [
            'id' => $apt->id,
            'title' => $apt->patient->name . ' - ' . $apt->type,
            'patient' => [
                'id' => $apt->patient->id,
                'name' => $apt->patient->name,
            ],
            'dentist' => $apt->dentist ? [
                'id' => $apt->dentist->id,
                'name' => $apt->dentist->name,
            ] : null,
            'start' => $apt->start_time->toISOString(),
            'end' => $apt->end_time->toISOString(),
            'status' => $apt->status,
            'type' => $apt->type,
            'notes' => $apt->notes,
        ]);

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

        // Calculate stats
        $now = now();
        $startOfDay = $now->copy()->startOfDay();
        $endOfDay = $now->copy()->endOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $stats = [
            'total_today' => Appointment::whereBetween('start_time', [$startOfDay, $endOfDay])->count(),
            'total_week' => Appointment::whereBetween('start_time', [$startOfWeek, $endOfWeek])->count(),
            'total_month' => Appointment::whereBetween('start_time', [$startOfMonth, $endOfMonth])->count(),
            'upcoming' => Appointment::where('start_time', '>', $now)->where('status', 'scheduled')->count(),
        ];

        return Inertia::render('Appointments/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'appointments' => $appointments,
            'patients' => $patients,
            'stats' => $stats,
            'appointmentTypes' => $appointmentTypes,
        ]);
    }

    // Store with validation for times, patient, dentist
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'dentist_id' => 'nullable|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'type' => 'required|string',
            'status' => 'in:scheduled,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $validated['start_time'] = $validated['date'] . ' ' . $validated['start_time'];
        $validated['end_time'] = $validated['date'] . ' ' . $validated['end_time'];
        unset($validated['date']);

        $appointment = Appointment::create($validated);

        // Queue reminder email
        // dispatch(new SendAppointmentReminder($appointment));

        return redirect()->route('appointments.index')->with('success', 'Appointment scheduled.');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'dentist_id' => 'nullable|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'type' => 'required|string',
            'status' => 'required|in:scheduled,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $validated['start_time'] = $validated['date'] . ' ' . $validated['start_time'];
        $validated['end_time'] = $validated['date'] . ' ' . $validated['end_time'];
        unset($validated['date']);

        $appointment->update($validated);

        return redirect()->route('appointments.index')->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->route('appointments.index')->with('success', 'Appointment deleted.');
    }

}