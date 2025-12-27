<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeminiService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(GeminiService::class, $this->service);
    }

    /** @test */
    public function it_returns_error_message_when_api_key_is_missing()
    {
        // Temporarily clear the API key
        config(['services.gemini.key' => null]);
        putenv('GEMINI_API_KEY=');

        $response = $this->service->chat('Hello');

        $this->assertEquals("I'm sorry, I'm not correctly configured yet. Please contact the administrator.", $response);
    }

    /** @test */
    public function it_makes_http_request_to_gemini_api()
    {
        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Hello! How can I help you with your dental clinic today?'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $response = $this->service->chat('Hello');

        $this->assertEquals('Hello! How can I help you with your dental clinic today?', $response);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'generativelanguage.googleapis.com') &&
                   $request->hasHeader('x-goog-api-key', 'test-api-key');
        });
    }

    /** @test */
    public function it_handles_api_error_response()
    {
        config(['services.gemini.key' => 'test-api-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API Error']], 400)
        ]);

        $response = $this->service->chat('Hello');

        $this->assertNull($response);
    }

    /** @test */
    public function it_handles_network_error()
    {
        config(['services.gemini.key' => 'test-api-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('', 500)
        ]);

        $response = $this->service->chat('Hello');

        $this->assertNull($response);
    }

    /** @test */
    public function it_includes_system_instruction_in_request()
    {
        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Response'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $this->service->chat('Hello');

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);
            
            // Check that system instruction is included
            $hasSystemInstruction = false;
            if (isset($payload['contents'][0]['parts'][0]['text'])) {
                $text = $payload['contents'][0]['parts'][0]['text'];
                $hasSystemInstruction = str_contains($text, 'You are a dental clinic AI assistant');
            }

            return $hasSystemInstruction;
        });
    }

    /** @test */
    public function it_includes_current_date_in_system_instruction()
    {
        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Response'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $this->service->chat('Hello');

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);
            
            if (isset($payload['contents'][0]['parts'][0]['text'])) {
                $text = $payload['contents'][0]['parts'][0]['text'];
                return str_contains($text, 'CURRENT DATE: ' . now()->format('Y-m-d'));
            }

            return false;
        });
    }

    /** @test */
    public function it_handles_conversation_history()
    {
        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Response with context'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $history = [
            ['role' => 'user', 'content' => 'Previous message'],
            ['role' => 'assistant', 'content' => 'Previous response']
        ];

        $this->service->chat('New message', $history);

        Http::assertSent(function ($request) use ($history) {
            $payload = json_decode($request->body(), true);
            
            // Check that history is included in the contents
            $contents = $payload['contents'] ?? [];
            
            // Should have system instruction + history + new message
            return count($contents) >= 3;
        });
    }

    /** @test */
    public function it_logs_error_when_api_key_is_missing()
    {
        // Clear the API key
        config(['services.gemini.key' => null]);
        putenv('GEMINI_API_KEY=');

        Log::shouldReceive('error')->once()->with('Gemini API key is missing.');

        $this->service->chat('Hello');
    }

    /** @test */
    public function it_returns_null_on_empty_response()
    {
        config(['services.gemini.key' => 'test-api-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => []], 200)
        ]);

        $response = $this->service->chat('Hello');

        $this->assertNull($response);
    }

    /** @test */
    public function it_handles_malformed_response()
    {
        config(['services.gemini.key' => 'test-api-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['invalid' => 'structure'], 200)
        ]);

        $response = $this->service->chat('Hello');

        $this->assertNull($response);
    }

    /** @test */
    public function it_sends_correct_content_type_header()
    {
        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Response'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $this->service->chat('Hello');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Content-Type', 'application/json');
        });
    }

    /** @test */
    public function it_can_extract_patient_creation_info()
    {
        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '{"action": "create_patient", "data": {"name": "John Doe", "email": "john@example.com", "phone": "+1234567890", "age": 30}}'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $message = "I want to create a new patient. His name is John Doe, email is john@example.com, phone is +1234567890, and he's 30 years old.";
        
        $response = $this->service->chat($message);

        $this->assertStringContainsString('create_patient', $response);
        $this->assertStringContainsString('John Doe', $response);
    }

    /** @test */
    public function it_can_extract_appointment_booking_info()
    {
        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '{"action": "book_appointment", "data": {"patient_name": "Jane Smith", "date": "2024-12-16", "time": "14:00", "type": "checkup"}}'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $message = "I want to book an appointment for Jane Smith tomorrow at 2pm for a checkup.";
        
        $response = $this->service->chat($message);

        $this->assertStringContainsString('book_appointment', $response);
        $this->assertStringContainsString('Jane Smith', $response);
    }
}
