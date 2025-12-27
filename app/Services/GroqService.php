<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('GROQ_API_KEY');
        $this->baseUrl = 'https://api.groq.com/openai/v1/chat/completions';
    }

    /**
     * Chat with Groq AI
     */
    public function chat(string $message, array $history = [])
    {
        if (!$this->apiKey) {
            Log::error('Groq API key is missing.');
            return "I'm sorry, I'm not correctly configured yet. Please contact the administrator.";
        }

        // System prompt with current date
        $systemInstruction = "You are a dental clinic AI assistant. Help with questions, create patients, and book appointments.

CLINIC INFORMATION:
- Location: Plot 8, Hill Road, Entebbe, Uganda
- Hours: Monday-Friday 9:00 AM - 6:00 PM
- Services: General Dentistry, Cosmetic Dentistry (Whitening, Veneers), Orthodontics (Braces, Invisalign), Implants
- Phone: +256 392911652
- Email: alvinpaka@gmail.com

CURRENT DATE: " . now()->format('Y-m-d') . " (use this for date conversions)

PATIENT CREATION: Extract name, email, phone, and either DOB (YYYY-MM-DD) or age. Return JSON:
{\"action\":\"create_patient\",\"confidence\":\"high/medium/low\",\"data\":{\"name\":\"\",\"email\":\"\",\"phone\":\"\",\"dob\":\"\" OR \"age\":\"\"},\"message\":\"I'll help you create a patient record. Please confirm.\"}

APPOINTMENT BOOKING: First verify patient exists, then extract patient_name, date (YYYY-MM-DD format, convert 'tomorrow' to " . now()->addDay()->format('Y-m-d') . ", 'today' to " . now()->format('Y-m-d') . "), time (HH:MM format, convert '2pm' to '14:00', '3pm' to '15:00'), type, and reason/notes (if mentioned). If patient doesn't exist, suggest creating patient first. Return JSON:
{\"action\":\"book_appointment\",\"confidence\":\"high/medium/low\",\"data\":{\"patient_name\":\"\",\"date\":\"\",\"time\":\"\",\"type\":\"\",\"reason\":\"\"},\"message\":\"I can book this appointment. Please confirm.\"}

NEED MORE INFO: If missing details, return a normal text message asking for the specific information needed. Example: \"I need more information to create a patient record. Please provide your email address and phone number.\"

GENERAL QUESTIONS: Respond normally with helpful dental clinic information using the clinic details above.

CRITICAL: For actions 1-2 (create_patient, book_appointment), return ONLY raw JSON without any markdown formatting, code blocks, or ``` symbols. NO MARKDOWN AT ALL. For actions 3-4 (gather_info, general questions), return normal text.";

        // Build messages array
        $messages = [
            ['role' => 'system', 'content' => $systemInstruction]
        ];

        // Add conversation history
        foreach ($history as $msg) {
            $role = $msg['role'] === 'assistant' ? 'assistant' : 'user';
            if ($msg['role'] === 'system') continue;
            
            if (!isset($msg['content']) || empty(trim($msg['content']))) continue;
            
            $messages[] = [
                'role' => $role,
                'content' => trim($msg['content'])
            ];
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'model' => 'llama-3.1-8b-instant', // Fast and free model
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 800,
            ]);

            if ($response->successful()) {
                $responseText = $response->json('choices.0.message.content');
                
                // Debug logging
                Log::info('Raw Groq Response: ' . $responseText);
                
                // Clean up response text - remove markdown code blocks if present
                $cleanedResponse = $responseText;
                
                if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $responseText, $matches)) {
                    $cleanedResponse = trim($matches[1]);
                }
                
                // Try to parse as JSON first
                $decoded = json_decode($cleanedResponse, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $cleanedResponse;
                }
                
                return $responseText;
            }

            Log::error('Groq API Error: ' . $response->body());
            return "I'm having trouble connecting to my brain right now. Please try again later.";

        } catch (\Exception $e) {
            Log::error('Groq Exception: ' . $e->getMessage());
            return "An unexpected error occurred. Please try again.";
        }
    }
}
