<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Appointment::class, 'appointment');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage <= 0) {
            $perPage = 10;
        }

        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $type = $request->input('type');
        $dentistId = $request->input('dentist_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sortBy = $request->input('sort_by', 'start_time');
        $sortOrder = $request->input('sort_order', 'asc');

        $appointmentsQuery = Appointment::with(['patient', 'dentist']);

        // Apply search
        if ($search !== '') {
            $appointmentsQuery->where(function($query) use ($search) {
                $query->whereHas('patient', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Apply filters
        if ($status && $status !== 'all') {
            $appointmentsQuery->where('status', $status);
        }

        if ($type && $type !== 'all') {
            $appointmentsQuery->where('type', $type);
        }

        if ($dentistId && $dentistId !== 'all') {
            $appointmentsQuery->where('user_id', $dentistId);
        }

        if ($startDate) {
            $appointmentsQuery->whereDate('start_time', '>=', $startDate);
        }

        if ($endDate) {
            $appointmentsQuery->whereDate('start_time', '<=', $endDate);
        }

        // Apply sorting
        $allowedSorts = ['start_time', 'end_time', 'status', 'type', 'created_at'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'start_time';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        $appointmentsQuery->orderBy($sortBy, $sortOrder);

        // Get calendar appointments (limited to 3 months for performance)
        $calendarStart = now()->startOfMonth();
        $calendarEnd = now()->addMonths(3)->endOfMonth();
        
        $calendarAppointments = (clone $appointmentsQuery)
            ->whereBetween('start_time', [$calendarStart, $calendarEnd])
            ->get()
            ->map(fn ($apt) => [
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

        // Get paginated appointments for the list view
        $appointments = $appointmentsQuery
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($apt) => [
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
            ])
            ->withQueryString();

        $patients = Patient::select('id', 'name', 'email')->get();
        $dentists = User::role('dentist')->select('id', 'name', 'email')->get();

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

        $appointmentStatuses = [
            'scheduled' => 'Scheduled',
            'confirmed' => 'Confirmed',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No Show',
            'rescheduled' => 'Rescheduled'
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
            'calendarAppointments' => $calendarAppointments,
            'patients' => $patients,
            'dentists' => $dentists,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type,
                'dentist_id' => $dentistId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
                'page' => (int) $request->input('page', 1),
                'total' => $appointments->total(),
                'from' => $appointments->firstItem(),
                'to' => $appointments->lastItem(),
            ],
            'appointmentTypes' => $appointmentTypes,
            'appointmentStatuses' => $appointmentStatuses,
            'stats' => $stats,
            'can' => [
                'createAppointment' => auth()->user()?->can('create', Appointment::class) ?? false,
                'updateAppointment' => auth()->user()?->can('update', new Appointment()) ?? false,
                'deleteAppointment' => auth()->user()?->can('delete', new Appointment()) ?? false,
            ],
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
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return redirect()->route('appointments.index')->with('success', 'Appointment deleted.');
    }

}