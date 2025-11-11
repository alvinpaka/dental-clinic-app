<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'version',
        'active',
        'signature_required',
    ];

    protected $casts = [
        'active' => 'boolean',
        'signature_required' => 'boolean',
    ];

    public function consents()
    {
        return $this->hasMany(Consent::class, 'template_id');
    }
}
