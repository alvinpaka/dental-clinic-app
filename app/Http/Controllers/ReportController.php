<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use App\Models\Invoice;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $revenueData = Invoice::where('status', 'paid')
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->get();

        $treatmentTrends = Treatment::selectRaw('`procedure`, COUNT(*) as count')
            ->groupBy('procedure')
            ->get();

        return Inertia::render('Reports/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'revenueData' => $revenueData,
            'treatmentTrends' => $treatmentTrends,
        ]);
    }
}