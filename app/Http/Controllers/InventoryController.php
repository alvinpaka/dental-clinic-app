<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    private const INVENTORY_CATEGORIES = [
        [
            'id' => 1,
            'name' => 'Consumables and Disposable Supplies',
            'description' => 'Single-use or short-life items frequently replaced.',
            'examples' => ['Gloves', 'Masks', 'Gauze', 'Patient bibs', 'Towels', 'Dental cements', 'Gypsum products']
        ],
        [
            'id' => 2,
            'name' => 'Dental Instruments and Tools',
            'description' => 'Reusable items requiring maintenance and sterilization.',
            'examples' => ['Forceps', 'Scalers', 'Mirrors', 'Sickle probes', 'Surgical kits', 'Handpieces']
        ],
        [
            'id' => 3,
            'name' => 'Dental Equipment',
            'description' => 'High-value, durable items depreciated over time.',
            'examples' => ['X-ray machines', 'Sterilizers', 'Dental chairs', 'Computers', 'Light curing units']
        ],
        [
            'id' => 4,
            'name' => 'Medications and Anesthetics',
            'description' => 'Regulated items with expiration tracking.',
            'examples' => ['Local anesthetics', 'Pain relievers', 'Antibiotics']
        ],
        [
            'id' => 5,
            'name' => 'Office Supplies',
            'description' => 'Administrative items for daily operations.',
            'examples' => ['Paper', 'Printer ink', 'Stationery']
        ],
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', InventoryItem::class);

        $perPage = (int) $request->input('per_page', 10);
        if ($perPage <= 0) {
            $perPage = 10;
        }

        $search = trim((string) $request->input('search', ''));
        $category = $request->input('category');
        $stockStatus = $request->input('stock_status');
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = InventoryItem::query();

        // Apply search
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        // Apply stock status filter
        if ($stockStatus) {
            switch ($stockStatus) {
                case 'low':
                    $query->where('quantity', '>', 0)
                          ->where('quantity', '<=', DB::raw('low_stock_threshold'));
                    break;
                case 'out':
                    $query->where('quantity', '<=', 0);
                    break;
                case 'in_stock':
                    $query->where('quantity', '>', 0);
                    break;
            }
        }

        // Apply sorting
        $allowedSorts = ['name', 'quantity', 'unit_price', 'category', 'created_at'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Get paginated results
        $items = $query->paginate($perPage)->withQueryString();
        
        // Compute stats (optimized to use the same query when possible)
        $totalItems = $query->toBase()->getCountForPagination();
        $lowStockItems = InventoryItem::where('quantity', '<=', DB::raw('low_stock_threshold'))
            ->where('quantity', '>', 0)
            ->when($category && $category !== 'all', function($q) use ($category) {
                $q->where('category', $category);
            })
            ->count();
            
        $outOfStock = InventoryItem::where('quantity', '<=', 0)
            ->when($category && $category !== 'all', function($q) use ($category) {
                $q->where('category', $category);
            })
            ->count();
            
        $totalValue = $query->clone()
            ->select(DB::raw('SUM(quantity * unit_price) as total'))
            ->value('total') ?? 0;

        return Inertia::render('Inventory/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'items' => $items,
            'categories' => self::INVENTORY_CATEGORIES,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'stock_status' => $stockStatus,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
                'page' => (int) $request->input('page', 1),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
            'stats' => [
                'total_items' => $totalItems,
                'low_stock_items' => $lowStockItems,
                'total_value' => $totalValue,
                'out_of_stock' => $outOfStock,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', InventoryItem::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|integer|min:1',
            'supplier' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
        ]);

        InventoryItem::create($validated);

        return redirect()->route('inventory.index')->with('success', 'Item added.');
    }

    public function update(Request $request, $id)
    {
        $inventoryItem = InventoryItem::findOrFail($id);

        $this->authorize('update', $inventoryItem);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|integer|min:1',
            'supplier' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
        ]);

        $inventoryItem->update($validated);

        return redirect()->route('inventory.index')->with('success', 'Item updated.')->setStatusCode(303);
    }

    public function destroy($id)
    {
        $inventoryItem = InventoryItem::findOrFail($id);

        $this->authorize('delete', $inventoryItem);
        $inventoryItem->delete();

        return redirect()->route('inventory.index')->with('success', 'Item deleted.')->setStatusCode(303);
    }
}