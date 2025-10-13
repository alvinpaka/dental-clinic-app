<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\OdontogramController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

// Auth routes (Breeze)
require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Resources
    Route::resource('patients', PatientController::class);
    Route::resource('appointments', AppointmentController::class);
    Route::resource('treatments', TreatmentController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::put('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    // Staff Management Routes
    Route::resource('staff', StaffController::class);
    Route::put('staff/{staff}/update-roles', [StaffController::class, 'updateRoles'])->name('staff.update-roles');
    Route::resource('inventory', InventoryController::class)->parameters(['inventory' => 'id'])->except(['create', 'edit', 'show']);
    Route::resource('prescriptions', PrescriptionController::class);

    // Odontogram
    Route::get('/patients/{patient}/odontogram', [OdontogramController::class, 'show'])->name('patients.odontogram.show');
    Route::post('/patients/{patient}/odontogram', [OdontogramController::class, 'store'])->name('patients.odontogram.store');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});