<?php

namespace App\Models;

use App\Enums\EstadoTraslado;
use App\Enums\TipoTraslado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        'tipo_traslado',
    ];

    protected $casts = [
        'salida_at' => 'datetime',
        'regreso_at' => 'datetime',
        'programado_at' => 'datetime',
        'tipo_traslado' => TipoTraslado::class,
        'estado_traslado' => EstadoTraslado::class,
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function bitacora(): HasMany
    {
        return $this->hasMany(Bitacora::class);
    }

    /** Supervisores ven todo; el resto solo lo que solicitó o conduce. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('manage-trips')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('solicitado_por', $user->id);

            if ($user->conductor) {
                $q->orWhere('conductor_id', $user->conductor->id);
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* Flujo de estados (cada transición queda en la bitácora) */
    /* ------------------------------------------------------------------ */

    public static function startSpontaneous(array $data, User $by): self
    {
        static::ensureAvailable((int) $data['vehiculo_id'], (int) $data['conductor_id']);

        return DB::transaction(function () use ($data, $by) {
            $traslado = static::create([
                ...$data,
                'tipo_traslado' => TipoTraslado::Spontaneous,
                'estado' => EstadoTraslado::EnCurso,
                'solicitado_por' => $by->id,
                'salida_at' => now(),
            ]);

            $traslado->addLog('salida', $by, 'Inicio de viaje espontáneo', $data['init_km'], $data['origen']);

            return $traslado;
        });
    }

    public function approve(User $by, int $vehicleId, int $driverId): void
    {
        $this->ensureStatus(EstadoTraslado::Solicitado);
        static::ensureAvailable($vehicleId, $driverId, $this);

        DB::transaction(function () use ($by, $vehicleId, $driverId) {
            $this->forceFill([
                'vehiculo_id' => $vehicleId,
                'conductor_id' => $driverId,
                'autorizado_by' => $by->id,
                'estado' => EstadoTraslado::Autorizado,
            ])->save();

            $this->load(['vehiculo', 'conductor']);
            $this->addLog('estado', $by, "Viaje autorizado. Vehículo {$this->vehiculo->patente}, chofer {$this->conductor->full_name}.");
        });
    }

    public function start(User $by, int $initialMileage): void
    {
        $this->ensureStatus(EstadoTraslado::Autorizado);

        if ($initialMileage < $this->vehiculo->actual_km) {
            throw ValidationException::withMessages([
                'init_km' => "No puede ser menor al kilometraje actual del vehículo ({$this->vehicle->actual_km} km).",
            ]);
        }

        static::ensureAvailable($this->vehiculo_id, $this->conductor_id, $this, onlyInProgress: true);

        DB::transaction(function () use ($by, $initialMileage) {
            $this->forceFill([
                'estado' => EstadoTraslado::EnCurso,
                'salida_at' => now(),
                'init_km' => $initialMileage,
            ])->save();

            $this->addLog('salida', $by, 'Inicio de viaje', $initialMileage, $this->origen);
        });
    }

    public function complete(User $by, int $finalMileage, ?string $notes = null): void
    {
        $this->ensureStatus(EstadoTraslado::EnCurso);

        if ($finalMileage < $this->init_km) {
            throw ValidationException::withMessages([
                'final_km' => "No puede ser menor al kilometraje inicial ({$this->init_km} km).",
            ]);
        }

        DB::transaction(function () use ($by, $finalMileage, $notes) {
            $this->forceFill([
                'estado' => EstadoTraslado::Finalizado,
                'regreso_at' => now(),
                'final_km' => $finalMileage,
                'distancia_km' => $finalMileage - $this->init_km,
                'observaciones' => $notes ?: $this->observaciones,
            ])->save();

            $this->vehiculo()->update(['actual_km' => $finalMileage]);

            $this->addLog('destino', $by, 'Término de viaje. Recorrido: '.$this->distancia_km.' km', $finalMileage, $this->destino);
        });
    }

    public function cancel(User $by, string $reason): void
    {
        $this->ensureStatus(EstadoTraslado::Solicitado, EstadoTraslado::Autorizado);

        DB::transaction(function () use ($by, $reason) {
            $this->forceFill(['estado' => EstadoTraslado::Cancelado])->save();
            $this->addLog('estado', $by, "Viaje cancelado: {$reason}");
        });
    }

    /* ------------------------------------------------------------------ */
    /* Helpers */
    /* ------------------------------------------------------------------ */

    public function addLog(string $event, ?User $by, ?string $description = null, ?int $mileage = null, ?string $location = null): Bitacora
    {
        return $this->logs()->create([
            'user_id' => $by?->id,
            'registrado_at' => now(),
            'event_type' => $event,
            'mileage_km' => $mileage,
            'location' => $location,
            'description' => $description,
        ]);
    }

    protected function ensureStatus(EstadoTraslado ...$allowed): void
    {
        if (! in_array($this->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'estado' => "Acción no permitida: el viaje está {$this->estado->label()}.",
            ]);
        }
    }

    /**
     * Un vehículo/chofer está ocupado si tiene un viaje en curso o,
     * para viajes programados, otro viaje autorizado el mismo día.
     */
    public static function ensureAvailable(int $vehicleId, int $driverId, ?self $except = null, bool $onlyInProgress = false): void
    {
        $isBusy = fn (string $column, int $id) => static::query()
            ->when($except, fn (Builder $q) => $q->whereKeyNot($except->getKey()))
            ->where($column, $id)
            ->where(function (Builder $q) use ($except, $onlyInProgress) {
                $q->where('estado', EstadoTraslado::EnCurso);

                if (! $onlyInProgress && $except?->programado_at) {
                    $q->orWhere(fn (Builder $q) => $q
                        ->where('estado', EstadoTraslado::Autorizado)
                        ->whereDate('programado_at', $except->programado_at));
                }
            })
            ->exists();

        $errors = [];

        if ($isBusy('vehiculo_id', $vehicleId)) {
            $errors['vehiculo_id'] = 'El vehículo no está disponible (viaje en curso o ya asignado ese día).';
        }

        if ($isBusy('conductor_id', $driverId)) {
            $errors['conductor_id'] = 'El chofer no está disponible (viaje en curso o ya asignado ese día).';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
