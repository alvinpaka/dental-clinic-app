<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OdontogramTooth extends Model
{
    use HasFactory;

    protected $fillable = [
        'odontogram_id',
        'tooth_code',
        'status',
        'note',
    ];

    public function odontogram()
    {
        return $this->belongsTo(Odontogram::class);
    }
}
