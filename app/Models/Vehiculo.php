<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'placa',
        'vin',
        'marca_id',
        'modelo_id',
        'anio',
        'color',
        'numero_motor',
        'transmision',
        'actual_km',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class);
    }

    public function conductorActual()
    {
        return $this->hasOne(Traslado::class)
            ->whereNull('regreso_at')
            ->latestOfMany('salida_at');
    }
}
