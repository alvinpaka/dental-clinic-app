<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'author_id',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'status',
        'signed_by',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
