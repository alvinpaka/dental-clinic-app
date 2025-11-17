<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class DemoController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Demo', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'preferred_date' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $to = config('mail.from.address');
            if ($to) {
                $subject = 'New Demo Request from '.$data['name'];
                $lines = [
                    'A new demo has been requested.',
                    '',
                    'Name: '.$data['name'],
                    'Email: '.$data['email'],
                    'Phone: '.($data['phone'] ?? ''),
                    'Company: '.($data['company'] ?? ''),
                    'Preferred Date: '.($data['preferred_date'] ?? ''),
                    '',
                    'Message:',
                    (string) ($data['message'] ?? ''),
                    '',
                    'Sent from: '.config('app.url'),
                ];
                Mail::raw(implode("\n", $lines), function ($msg) use ($to, $subject) {
                    $msg->to($to)->subject($subject);
                });
            }
        } catch (\Throwable $e) {
            // swallow mail errors in public form
        }

        return back()->with('status', 'Thanks! We\'ll contact you shortly to schedule your demo.');
    }
}
