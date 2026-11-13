<?php

namespace App\Models;

use App\Enums\EstadoTraslado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehiculo extends Model
{
    use SoftDeletes;

    //public const TYPES = ['Automóvil', 'Camioneta', 'Furgón', 'Minibús', 'Bus', 'Camión'];

    protected $fillable = [
        'patente',
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
        'anio' => 'integer',
        'actual_km' => 'integer',
    ];

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class);
    }

    public function traslados(): HasMany
    {
        return $this->hasMany(Traslado::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Activo y sin viaje en curso. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()->whereDoesntHave(
            'traslados',
            fn (Builder $q) => $q->where('estado', EstadoTraslado::EnCurso)
        );
    }

    public function conductorActual()
    {
        return $this->hasOne(Traslado::class)
            ->whereNull('regreso_at')
            ->latestOfMany('salida_at');
    }
}
