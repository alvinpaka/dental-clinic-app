<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 
        'treatment_id', 
        'prescription_id', 
        'amount', 
        'status', 
        'due_date', 
        'pdf_path', 
        'notes', 
        'created_by'
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'float',
    ];

    protected $appends = [
        'paid_total',
        'balance',
        'payment_status',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function items()
    {
        return $this->hasMany(\App\Models\InvoiceItem::class);
    }

    public function getPaidTotalAttribute(): float
    {
        // Use loaded relation when available to avoid extra query and ensure accuracy
        $paymentsSum = $this->relationLoaded('payments')
            ? (float) $this->payments->sum('amount')
            : (float) $this->payments()->sum('amount');
        $refundsSum = (float) (\App\Models\PaymentRefund::query()->where('invoice_id', $this->id)->sum('amount'));
        return round($paymentsSum - $refundsSum, 2);
    }

    public function getBalanceAttribute(): float
    {
        // For historical records that were marked paid without payments, force balance to 0
        if (($this->status ?? null) === 'paid') {
            return 0.0;
        }
        $paid = $this->paid_total; // already numeric
        $balance = (float) $this->amount - (float) $paid;
        return round(max(0, $balance), 2);
    }

    public function getPaymentStatusAttribute(): string
    {
        $paid = (float) $this->paid_total;
        $amount = (float) $this->amount;
        if ($amount <= 0) {
            return 'paid';
        }
        if ($paid <= 0) {
            return 'pending';
        }
        if ($paid > 0 && $paid < $amount) {
            return 'partial';
        }
        return 'paid';
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}