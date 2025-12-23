<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
    }

    /**
     * Send a message to the Google Gemini API and get the response.
     *
     * @param string $message
     * @param array $history Optional conversation history
     * @return string|null
     */
    public function chat(string $message, array $history = [])
    {
        if (!$this->apiKey) {
            Log::error('Gemini API key is missing.');
            return "I'm sorry, I'm not correctly configured yet. Please contact the administrator.";
        }

        // System prompt to define the agent's persona
        // Gemini sends system instructions differently, but for simplicity in this REST call,
        // we can prepend it to the history or just rely on the context.
        // For better structure, we'll prepend it as a 'user' message with a clear instruction,
        // as the 'system' role is not always strictly supported in the basic generateContent endpoint without specific config.
        $systemInstruction = "You are a dental clinic AI assistant. Help with questions, create patients, and book appointments.

CURRENT DATE: " . now()->format('Y-m-d') . " (use this for date conversions)

PATIENT CREATION: Extract name, email, phone, and either DOB (YYYY-MM-DD) or age. Return JSON:
{\"action\":\"create_patient\",\"confidence\":\"high/medium/low\",\"data\":{\"name\":\"\",\"email\":\"\",\"phone\":\"\",\"dob\":\"\" OR \"age\":\"\"},\"message\":\"I'll help you create a patient record. Please confirm.\"}

APPOINTMENT BOOKING: Extract patient_name, date (YYYY-MM-DD format, convert 'tomorrow' to " . now()->addDay()->format('Y-m-d') . ", 'today' to " . now()->format('Y-m-d') . "), time (HH:MM format, convert '2pm' to '14:00', '3pm' to '15:00'), type. Return JSON:
{\"action\":\"book_appointment\",\"confidence\":\"high/medium/low\",\"data\":{\"patient_name\":\"\",\"date\":\"\",\"time\":\"\",\"type\":\"\"},\"message\":\"I can book this appointment. Please confirm.\"}

NEED MORE INFO: If missing details, return:
{\"action\":\"gather_info\",\"confidence\":\"low\",\"data\":{\"intent\":\"create_patient/book_appointment\",\"missing_fields\":[\"field1\",\"field2\"]},\"message\":\"I need more information. Please provide...\"}

GENERAL QUESTIONS: Respond normally with helpful dental clinic information.

CRITICAL: For actions 1-3, return ONLY raw JSON without any markdown formatting, code blocks, or ``` symbols. NO MARKDOWN AT ALL. For action 4, return normal text.";

        // Transform history to Gemini format: { role: 'user'|'model', parts: [{ text: '...' }] }
        $contents = [];
        
        // Add system instruction as the first user message context
        $contents[] = [
            'role' => 'user',
            'parts' => [["text" => "System Instruction: " . $systemInstruction]]
        ];
        
        // Add a model acknowledgment to keep the flow natural
        $contents[] = [
            'role' => 'model',
            'parts' => [["text" => "Understood. I am ready to assist patients as the Dental Clinic Assistant."]]
        ];

        foreach ($history as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            // Skip system messages from the old format if any
            if ($msg['role'] === 'system') continue;
            
            // Skip messages with empty or null content
            if (!isset($msg['content']) || empty(trim($msg['content']))) continue;
            
            $contents[] = [
                'role' => $role,
                'parts' => [["text" => trim($msg['content'])]]
            ];
        }

        // Add the current user message
        $contents[] = [
            'role' => 'user',
            'parts' => [["text" => $message]]
        ];

        try {
            // Debug logging
            Log::info('Gemini Request Contents: ' . json_encode($contents));
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 800,
                ]
            ]);

            if ($response->successful()) {
                $responseText = $response->json('candidates.0.content.parts.0.text');
                
                // Debug logging
                Log::info('Raw Gemini Response: ' . $responseText);
                
                // Clean up response text - remove markdown code blocks if present
                $cleanedResponse = $responseText;
                
                // Check if response is wrapped in markdown code blocks (handle multiline)
                if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $responseText, $matches)) {
                    $cleanedResponse = trim($matches[1]);
                    Log::info('Cleaned JSON Response: ' . $cleanedResponse);
                }
                
                // Try to parse as JSON first
                $decoded = json_decode($cleanedResponse, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Return the structured response
                    return $cleanedResponse;
                }
                
                // Return regular text response (cleaned)
                return $responseText;
            }

            Log::error('Gemini API Error: ' . $response->body());
            return "I'm having trouble connecting to my brain right now. Please try again later.";

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return "An unexpected error occurred. Please try again.";
        }
    }
}
