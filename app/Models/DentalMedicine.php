<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalMedicine extends Model
{
    protected $table = 'dental_medicines';
    protected $primaryKey = 'medicine_id';
    
    protected $fillable = [
        'medicine_name',
        'category',
        'dosage_form',
        'common_uses',
        'prescription_required',
    ];
    
    protected $casts = [
        'prescription_required' => 'boolean',
    ];
}
