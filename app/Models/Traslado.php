<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Traslado extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vehiculo_id',
        'conductor_id',
        'salida_at',
        'regreso_at',
        'origen',
        'destino',
        'init_km',
        'final_km',
        'distancia_km',
        'estado',
        'observaciones',
        'solicitado_por',
        'autorizado_por',
        'programado_at',
        'proposito',
        'cantidad_pasajeros',
        'tipo_traslado'
    ];

    protected $casts = [
        'salida_at' => 'datetime',
        'regreso_at' => 'datetime',
        'programado_at' => 'datetime'
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function conductor()
    {
        return $this->belongsTo(Conductor::class);
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function autorizador()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function bitacora()
    {
        return $this->hasMany(Bitacora::class);
    }
}
