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

    public function index()
    {
        $expenses = Expense::orderBy('date', 'desc')
            ->paginate(10);

        $stats = [
            'total_expenses' => Expense::count(),
            'total_amount' => Expense::sum('amount'),
            'this_month_expenses' => Expense::whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->count(),
            'this_month_amount' => Expense::whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('amount'),
        ];

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'stats' => $stats,
            'categories' => $this->getExpenseCategories(),
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
            'category' => 'required|string|in:' . implode(',', $this->getExpenseCategories()),
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
            'category' => 'required|string|in:' . implode(',', $this->getExpenseCategories()),
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
        return [
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
    }
}
