<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
        return Inertia::render('Inventory/Index', [
            'items' => $items,
            'categories' => self::INVENTORY_CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|integer|min:1',
        ]);

        InventoryItem::create($validated);

        return redirect()->route('inventory.index')->with('success', 'Item added.');
    }

    // ... other methods, including update quantity
}