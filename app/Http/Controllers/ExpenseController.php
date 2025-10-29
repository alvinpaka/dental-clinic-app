<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Expense::class, 'expense');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage <= 0) {
            $perPage = 10;
        }

        // Get filter parameters
        $search = $request->input('search', '');
        $category = $request->input('category', '');
        $status = $request->input('status', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $minAmount = $request->input('min_amount');
        $maxAmount = $request->input('max_amount');
        $sortBy = $request->input('sort_by', 'date');
        $sortOrder = $request->input('sort_order', 'desc');

        // Validate sort parameters
        $allowedSorts = ['date', 'amount', 'created_at', 'category'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'date';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // Build the query
        $query = Expense::query();

        // Apply search filter
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if (!empty($category)) {
            $query->where('category', $category);
        }

        // Apply status filter
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Apply date range filter
        if (!empty($startDate)) {
            $query->whereDate('date', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $query->whereDate('date', '<=', $endDate);
        }

        // Apply amount range filter
        if (!empty($minAmount)) {
            $query->where('amount', '>=', (float) $minAmount);
        }
        if (!empty($maxAmount)) {
            $query->where('amount', '<=', (float) $maxAmount);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate the results
        $expenses = $query->paginate($perPage)->withQueryString();

        // Get statistics for the current filters
        $stats = [
            'total_expenses' => $expenses->total(),
            'total_amount' => $expenses->sum('amount'),
            'this_month_expenses' => (clone $query)
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->count(),
            'this_month_amount' => (clone $query)
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('amount'),
        ];

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'stats' => $stats,
            'categories' => $this->getExpenseCategories(),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
                'page' => $expenses->currentPage(),
                'total' => $expenses->total(),
                'from' => $expenses->firstItem(),
                'to' => $expenses->lastItem(),
            ],
            'can' => [
                'createExpense' => auth()->user()?->can('create', Expense::class) ?? false,
                'updateExpense' => auth()->user()?->can('update', new Expense()) ?? false,
                'deleteExpense' => auth()->user()?->can('delete', new Expense()) ?? false,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Expenses/Create', [
            'categories' => $this->getExpenseCategories(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'date' => 'required|date',
        ]);

        Expense::create($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense created successfully.');
    }

    public function show(Expense $expense)
    {
        return Inertia::render('Expenses/Show', [
            'expense' => $expense,
        ]);
    }

    public function edit(Expense $expense)
    {
        return Inertia::render('Expenses/Edit', [
            'expense' => $expense,
            'categories' => $this->getExpenseCategories(),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'date' => 'required|date',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
    }

    private function getExpenseCategories(): array
    {
        // Note: These are operational business expenses, NOT inventory items.
        $categoryNames = [
            'Utilities',
            'Rent & Facilities',
            'Marketing & Advertising',
            'Insurance',
            'Staff Training & Education',
            'Equipment Maintenance',
            'Software & Technology',
            'Professional Services',
            'Travel & Transportation',
            'Cleaning & Sanitation',
            'Miscellaneous',
        ];

        return array_map(function ($name, $index) {
            return [
                'id' => $index + 1,
                'name' => $name,
                'description' => '', // Can add descriptions if needed
                'examples' => [], // Can add examples if needed
            ];
        }, $categoryNames, array_keys($categoryNames));
    }
}