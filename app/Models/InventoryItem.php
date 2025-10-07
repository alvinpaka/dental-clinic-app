<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'quantity', 'unit_price', 'low_stock_threshold', 'category'];

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }
}