<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'template_id',
        'template_version',
        'title',
        'content_snapshot',
        'signed_by_name',
        'signed_by_user_id',
        'signed_at',
        'ip_address',
        'signature_path',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function template()
    {
        return $this->belongsTo(ConsentTemplate::class, 'template_id');
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }
}
