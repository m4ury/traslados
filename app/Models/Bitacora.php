<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $fillable = [
        'traslado_id',
        'user_id',
        'registrado_at',
        'event_type',
        'mileage_km',
        'location',
        'description',
    ];

    protected $casts = [
        'registrado_at' => 'datetime',
    ];

    public function traslado()
    {
        return $this->belongsTo(Traslado::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
