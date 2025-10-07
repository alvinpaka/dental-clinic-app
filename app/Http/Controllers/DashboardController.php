<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Invoice;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_patients' => Patient::count(),
                'upcoming_appointments' => Appointment::where('start_time', '>', now())->count(),
                'monthly_revenue' => Invoice::whereMonth('created_at', now()->month)->where('status', 'paid')->sum('amount'),
                'low_stock_items' => \App\Models\InventoryItem::whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            ],
        ]);
    }
}