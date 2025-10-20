<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id', 'treatment_id', 'prescription_id', 'amount', 'status', 'due_date', 'pdf_path', 'notes'];

    protected $casts = ['due_date' => 'date'];

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
        // Return prescription data from the associated treatment
        return $this->treatment();
    }
}