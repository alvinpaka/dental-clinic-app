<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
        'method',
        'received_at',
        'reference',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'amount' => 'decimal:2',
        'receipt_number' => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->receipt_number)) {
                // Simple sequential assignment; acceptable for low write concurrency
                $next = (int) (\DB::table('payments')->max('receipt_number') ?? 0) + 1;
                $payment->receipt_number = $next;
            }
        });
    }
}
