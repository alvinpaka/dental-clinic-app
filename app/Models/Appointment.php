<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasClinicScope;

class Appointment extends Model
{
    use HasFactory, HasClinicScope;

    protected $fillable = ['patient_id', 'dentist_id', 'start_time', 'end_time', 'status', 'type', 'notes', 'clinic_id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}