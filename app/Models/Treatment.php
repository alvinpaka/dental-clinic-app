<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;
use App\Models\TreatmentProcedure;

class Treatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'cost',
        'notes',
        'file_path',
        // Prescription fields
        'medicine_id',
        'medication',
        'dosage',
        'frequency',
        'duration',
        'prescription_amount',
        'prescription_issue_date',
        'prescription_expiry_date',
        'prescription_instructions',
        'max_refills',
        'prescription_status',
        'refill_count'
    ];

    protected $appends = ['procedure'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function medicine()
    {
        return $this->belongsTo(DentalMedicine::class, 'medicine_id', 'medicine_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function procedures()
    {
        return $this->hasMany(TreatmentProcedure::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function getProcedureAttribute(): string
    {
        if (! $this->relationLoaded('procedures')) {
            $this->load('procedures');
        }

        return $this->procedures
            ->pluck('name')
            ->filter()
            ->implode(', ');
    }
}