<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_session_id',
        'type',
        'method',
        'amount',
        'reason',
        'payment_id',
        'refund_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function session()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function refund()
    {
        return $this->belongsTo(PaymentRefund::class, 'refund_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
