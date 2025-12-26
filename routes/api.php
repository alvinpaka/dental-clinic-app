<?php

use App\Http\Controllers\AiChatController;
use Illuminate\Support\Facades\Route;

// API Routes
Route::post('/chat', [AiChatController::class, 'chat'])->name('api.chat');
Route::post('/chat/patient', [AiChatController::class, 'createPatient'])->name('api.chat.patient');
Route::post('/chat/appointment', [AiChatController::class, 'bookAppointment'])->name('api.chat.appointment');
