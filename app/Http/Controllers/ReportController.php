<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'monthly'); // daily, weekly, monthly, annually
        
        $data = $this->getFinancialData($period);
        
        return Inertia::render('Reports/Index', [
            'revenueData' => $data['revenue'],
            'inventoryCosts' => $data['inventoryCosts'],
            'operationalExpenses' => $data['operationalExpenses'],
            'treatmentTrends' => $data['treatmentTrends'],
            'stats' => $data['stats'],
            'currentPeriod' => $period,
        ]);
    }

    private function getFinancialData($period)
    {
        $now = Carbon::now();
        
        // Determine date range and grouping
        switch ($period) {
            case 'daily':
                $startDate = $now->copy()->subDays(30);
                $groupBy = "DATE(created_at)";
                $labelFormat = 'M d';
                break;
            case 'weekly':
                $startDate = $now->copy()->subWeeks(12);
                $groupBy = "YEARWEEK(created_at, 1)";
                $labelFormat = 'W';
                break;
            case 'annually':
                $startDate = $now->copy()->subYears(5);
                $groupBy = "YEAR(created_at)";
                $labelFormat = 'Y';
                break;
            case 'monthly':
            default:
                $startDate = $now->copy()->subMonths(12);
                $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
                $labelFormat = 'M Y';
                break;
        }

        // Get revenue data (from paid invoices)
        $revenue = Invoice::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("{$groupBy} as period, SUM(amount) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) use ($period, $labelFormat) {
                return [
                    'period' => $this->formatPeriodLabel($item->period, $period, $labelFormat),
                    'total' => (float) $item->total,
                ];
            });

        // Get inventory costs (cost of purchasing stock/supplies)
        $inventoryCosts = InventoryItem::where('created_at', '>=', $startDate)
            ->selectRaw("{$groupBy} as period, SUM(quantity * unit_price) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) use ($period, $labelFormat) {
                return [
                    'period' => $this->formatPeriodLabel($item->period, $period, $labelFormat),
                    'total' => (float) $item->total,
                    'type' => 'inventory'
                ];
            });

        // Get operational expenses (business overhead costs)
        $operationalExpenses = Expense::where('date', '>=', $startDate)
            ->selectRaw("{$groupBy} as period, SUM(amount) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) use ($period, $labelFormat) {
                return [
                    'period' => $this->formatPeriodLabel($item->period, $period, $labelFormat),
                    'total' => (float) $item->total,
                    'type' => 'operational'
                ];
            });

        // Get treatment trends
        $treatmentTrends = TreatmentProcedure::query()
            ->select('treatment_procedures.name')
            ->selectRaw('COUNT(*) as total_count')
            ->join('treatments', 'treatment_procedures.treatment_id', '=', 'treatments.id')
            ->where('treatments.created_at', '>=', $startDate)
            ->groupBy('treatment_procedures.name')
            ->orderByDesc('total_count')
            ->limit(10)
            ->get()
            ->map(function ($trend) {
                return [
                    'procedure' => $trend->name,
                    'count' => (int) $trend->total_count,
                ];
            });

        // Calculate statistics
        $totalRevenue = Invoice::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');
            
        $totalInventoryCosts = InventoryItem::where('created_at', '>=', $startDate)
            ->selectRaw('SUM(quantity * unit_price) as total')
            ->value('total') ?? 0;
            
        $totalOperationalExpenses = Expense::where('date', '>=', $startDate)
            ->sum('amount');
            
        $totalExpenses = $totalInventoryCosts + $totalOperationalExpenses;
        $netProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        $stats = [
            'totalRevenue' => (float) $totalRevenue,
            'totalInventoryCosts' => (float) $totalInventoryCosts,
            'totalOperationalExpenses' => (float) $totalOperationalExpenses,
            'totalExpenses' => (float) $totalExpenses,
            'netProfit' => (float) $netProfit,
            'profitMargin' => round($profitMargin, 2),
            'totalPatients' => Treatment::distinct('patient_id')
                ->where('created_at', '>=', $startDate)
                ->count('patient_id'),
            'totalTreatments' => Treatment::where('created_at', '>=', $startDate)->count(),
        ];

        return [
            'revenue' => $revenue,
            'inventoryCosts' => $inventoryCosts,
            'operationalExpenses' => $operationalExpenses,
            'treatmentTrends' => $treatmentTrends,
            'stats' => $stats,
        ];
    }

    private function formatPeriodLabel($period, $periodType, $format)
    {
        try {
            if ($periodType === 'weekly') {
                $year = substr($period, 0, 4);
                $week = substr($period, 4);
                return "Week {$week}, {$year}";
            }
            
            return Carbon::parse($period)->format($format);
        } catch (\Exception $e) {
            return $period;
        }
    }
}