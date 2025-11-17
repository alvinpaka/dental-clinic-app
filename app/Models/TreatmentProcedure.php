<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentProcedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'treatment_id',
        'name',
        'cost',
    ];

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }
}
