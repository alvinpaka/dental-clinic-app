<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 
        'dentist_id', 
        'medicine_id',
        'medication', 
        'dosage', 
        'frequency',
        'duration',
        'issue_date', 
        'expiry_date',
        'instructions',
        'max_refills',
        'status',
        'refill_count'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'max_refills' => 'integer',
        'refill_count' => 'integer'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function medicine()
    {
        return $this->belongsTo(DentalMedicine::class, 'medicine_id', 'medicine_id');
    }
}