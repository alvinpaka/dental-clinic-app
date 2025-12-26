<?php

namespace App\Http\Controllers;

use App\Services\GroqService;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiChatController extends Controller
{
    protected $groqService;

    public function __construct(GroqService $groqService)
    {
        $this->groqService = $groqService;
    }

    /**
     * Handle the incoming chat message.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'sometimes|array', // Optional: to maintain context
        ]);

        $message = $request->input('message');
        $history = $request->input('history', []);

        // Limit history to last 10 messages to save tokens/context
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }

        $response = $this->groqService->chat($message, $history);

        // Check if response is JSON (structured response)
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            Log::info('Structured response detected: ' . json_encode($decoded));
            return response()->json([
                'response' => $decoded['message'] ?? $response,
                'structured' => true,
                'data' => $decoded
            ]);
        }

        return response()->json([
            'response' => $response,
            'structured' => false
        ]);
    }

    /**
     * Create a patient from chat interaction.
     */
    public function createPatient(Request $request)
    {
        // Debug logging
        Log::info('Chat patient request: ' . json_encode($request->all()));
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string|max:20',
            'dob' => 'nullable|date',
            'age' => 'nullable|integer|min:0|max:150',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Chat patient validation errors: ' . json_encode($validator->errors()->toArray()));
            return response()->json([
                'success' => false,
                'message' => 'Please provide all required information.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if patient with email already exists
            if ($request->email) {
                $existingPatient = Patient::where('email', $request->email)->first();
                if ($existingPatient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A patient with this email already exists.',
                        'patient_id' => $existingPatient->id
                    ], 409);
                }
            }

            // Prepare patient data
            $patientData = $validator->validated();
            
            // Handle DOB/age conversion
            if (!isset($patientData['dob']) || empty($patientData['dob'])) {
                if (isset($patientData['age']) && !empty($patientData['age'])) {
                    // Convert age to DOB
                    $patientData['dob'] = now()->subYears($patientData['age'])->format('Y-m-d');
                    unset($patientData['age']); // Remove age as we now have DOB
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Date of birth or age is required for patient records.'
                    ], 422);
                }
            } else {
                // DOB provided, remove age if it exists
                unset($patientData['age']);
            }

            $patient = Patient::create($patientData);

            return response()->json([
                'success' => true,
                'message' => "Patient record created successfully for {$patient->name}!",
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'email' => $patient->email,
                    'phone' => $patient->phone,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Chat patient creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'I encountered an error while creating the patient record. Please try again or contact the clinic directly.'
            ], 500);
        }
    }

    /**
     * Book an appointment from chat interaction.
     */
    public function bookAppointment(Request $request)
    {
        // Debug logging
        Log::info('Chat appointment request: ' . json_encode($request->all()));
        
        $validator = Validator::make($request->all(), [
            'patient_name' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|string',
            'type' => 'required|string',
            'notes' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Chat appointment validation errors: ' . json_encode($validator->errors()->toArray()));
            return response()->json([
                'success' => false,
                'message' => 'Please provide all required appointment details.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find patient by name - case-insensitive and flexible matching
            $searchName = strtolower($request->patient_name);
            $patient = Patient::whereRaw('LOWER(name) LIKE ?', ['%' . $searchName . '%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%' . str_replace(' ', '%', $searchName) . '%'])
                        ->first();
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'I couldn\'t find a patient with that name. Please make sure the patient is registered first.',
                    'action' => 'create_patient'
                ], 404);
            }

            // Parse time and create datetime objects
            $startDateTime = $request->date . ' ' . $request->time;
            $endDateTime = date('Y-m-d H:i', strtotime($startDateTime) + 3600); // Add 1 hour

            // Check for existing appointments at the same time
            $conflictingAppointment = Appointment::where('start_time', $startDateTime)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($conflictingAppointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'There is already an appointment scheduled at that time. Please choose a different time.',
                    'conflict' => true
                ], 409);
            }

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'dentist_id' => null, // Can be assigned later
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'type' => $request->type,
                'status' => 'scheduled',
                'notes' => $request->reason ?? $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Appointment booked successfully for {$patient->name} on " . 
                            date('F j, Y \a\t g:i A', strtotime($startDateTime)) . "!",
                'appointment' => [
                    'id' => $appointment->id,
                    'patient_name' => $patient->name,
                    'date' => $request->date,
                    'time' => $request->time,
                    'type' => $appointment->type,
                    'status' => $appointment->status,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Chat appointment booking error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'I encountered an error while booking the appointment. Please try again or contact the clinic directly.'
            ], 500);
        }
    }
}
