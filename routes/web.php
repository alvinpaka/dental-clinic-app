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
use App\Http\Controllers\ExpenseController;
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

    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])
        ->middleware('can:view,invoice')
        ->name('invoices.download');

    Route::put('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])
        ->middleware('can:update,invoice')
        ->name('invoices.mark-paid');
    // Staff Management Routes
    Route::resource('staff', StaffController::class);

    Route::put('staff/{staff}/update-roles', [StaffController::class, 'updateRoles'])
        ->middleware('can:update,staff')
        ->name('staff.update-roles');

    Route::resource('inventory', InventoryController::class)
        ->parameters(['inventory' => 'id'])
        ->except(['create', 'edit', 'show']);

    Route::resource('expenses', ExpenseController::class);

    // Odontogram
    Route::get('/patients/{patient}/odontogram', [OdontogramController::class, 'show'])
        ->middleware('can:view,patient')
        ->name('patients.odontogram.show');

    Route::post('/patients/{patient}/odontogram', [OdontogramController::class, 'store'])
        ->middleware('can:update,patient')
        ->name('patients.odontogram.store');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('can:viewReports')
        ->name('reports.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('treatments/{treatment}/create-invoice', [TreatmentController::class, 'createInvoice'])->name('treatments.createInvoice');
});