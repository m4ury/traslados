<?php

namespace App\Models;

use App\Enums\EstadoTraslado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conductor extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'rut',
        'phone',
        'license_number',
        'license_expires_at',
        'en_turno',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'license_expires_at' => 'date',
            'is_on_duty' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function traslados(): HasMany
    {
        return $this->hasMany(Traslado::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Activo, de turno y sin viaje en curso (para viajes espontáneos). */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()
            ->where('en_turno', true)
            ->whereDoesntHave('traslados', fn (Builder $q) => $q->where('estado', EstadoTraslado::EnCurso));
    }
}
