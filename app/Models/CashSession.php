<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'opened_by',
        'closed_by',
        'opening_amount',
        'closing_amount',
        'expected_cash_at_close',
        'variance',
        'started_at',
        'ended_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_cash_at_close' => 'decimal:2',
        'variance' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function movements()
    {
        return $this->hasMany(CashMovement::class);
    }

    public static function activeForUser(int $userId): ?self
    {
        return static::where('opened_by', $userId)
            ->where('status', 'open')
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();
    }
}
