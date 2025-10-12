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

    public function index()
    {
        $items = InventoryItem::paginate(10);
        
        // Compute stats
        $totalItems = InventoryItem::count();
        $lowStockItems = InventoryItem::where('quantity', '<=', DB::raw('low_stock_threshold'))->where('quantity', '>', 0)->count();
        $outOfStock = InventoryItem::where('quantity', 0)->count();
        $totalValue = InventoryItem::sum(DB::raw('quantity * unit_price'));

        return Inertia::render('Inventory/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'items' => $items,
            'categories' => self::INVENTORY_CATEGORIES,
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
        $inventoryItem->delete();

        return redirect()->route('inventory.index')->with('success', 'Item deleted.')->setStatusCode(303);
    }
}