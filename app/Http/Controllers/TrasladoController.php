<?php

namespace App\Http\Controllers;

use App\Enums\EstadoTraslado;
use App\Enums\TipoTraslado;
use App\Models\Conductor;
use App\Models\Traslado;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TrasladoController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only('estado', 'tipo_traslado', 'search');

        $traslados = Traslado::query()
            ->visibleTo($user)
            ->with(['vehiculo.marca:id,name', 'vehiculo.modelo:id,name', 'conductor:id,full_name', 'requester:id,name'])
            ->when($filters['estado'] ?? null, fn ($q, $s) => $q->where('estado', $s))
            ->when($filters['tipo_traslado'] ?? null, fn ($q, $t) => $q->where('tipo_traslado', $t))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('origen', 'like', "%{$s}%")
                ->orWhere('destino', 'like', "%{$s}%")
                ->orWhereHas('vehiculo', fn ($v) => $v->where('patente', 'like', '%'.strtoupper($s).'%'))
                ->orWhereHas('conductor', fn ($d) => $d->where('rut', 'like', "%{$s}%"))))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = Traslado::query()
            ->visibleTo($user)
            ->toBase()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return Inertia::render('traslados/Index', [
            'traslados' => $traslados,
            'counts' => $counts,
            'filters' => $filters,
            'can' => [
                'manage' => $user->can('manage-traslados'),
                'spontaneous' => $user->can('manage-traslados') || (bool) $user->conductor,
            ],
        ]);
    }

    /* ---------------------------- Espontáneo ---------------------------- */

    public function createSpontaneous(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->can('manage-traslados') || $user->conductor, 403);

        return Inertia::render('traslados/CreateSpontaneous', [
            'vehiculos' => Vehiculo::available()
                ->with(['marca:id,name', 'modelo:id,name'])
                ->orderBy('patente')
                ->get(),
            'conductor' => Conductor::available()->orderBy('rut')->get(['id', 'rut']),
            'currentDriverId' => $user->conductor?->id,
            'canChooseDriver' => $user->can('manage-traslados'),
        ]);
    }

    public function storeSpontaneous(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('manage-traslados') || $user->conductor, 403);

        // Un chofer solo puede registrar viajes a su nombre.
        if (! $user->can('manage-traslados')) {
            $request->merge(['conductor_id' => $user->conductor->id]);
        }

        $data = $request->validate([
            'vehiculo_id' => ['required', Rule::exists('vehiculos', 'id')->where('is_active', true)],
            'conductor_id' => ['required', Rule::exists('conductores', 'id')->where('activo', true)->where('en_turno', true)],
            'origen' => ['required', 'string', 'max:255'],
            'destino' => ['required', 'string', 'max:255'],
            'proposito' => ['nullable', 'string', 'max:255'],
            'cantidad_pasajeros' => ['required', 'integer', 'min:1', 'max:60'],
            'init_km' => ['required', 'integer', 'min:0'],
        ], [
            'conductor_id.exists' => 'El chofer debe estar activo y de turno.',
        ]);

        $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);

        if ($data['init_km'] < $vehiculo->actual_km) {
            throw ValidationException::withMessages([
                'init_km' => "No puede ser menor al kilometraje actual del vehículo ({$vehicle->actual_km} km).",
            ]);
        }

        $traslado = Traslado::startSpontaneous($data, $user);

        return to_route('traslados.show', $traslado)->with('success', 'Viaje espontáneo iniciado y registrado en la bitácora.');
    }

    /* ---------------------------- Programado ---------------------------- */

    public function createRequest(): Response
    {
        return Inertia::render('traslados/CreateRequest');
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'programado_at' => ['required', 'date', 'after:now'],
            'origen' => ['required', 'string', 'max:255'],
            'destino' => ['required', 'string', 'max:255'],
            'proposito' => ['required', 'string', 'max:255'],
            'cantidad_pasajeros' => ['required', 'integer', 'min:1', 'max:60'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'programado_at.after' => 'La fecha de salida debe ser futura.',
        ]);

        $traslado = DB::transaction(function () use ($data, $user) {
            $traslado = Traslado::create([
                ...$data,
                'tipo_traslado' => TipoTraslado::Scheduled,
                'estado' => EstadoTraslado::Solicitado,
                'solicitado_by' => $user->id,
            ]);

            $trip->addLog('status', $user, 'Solicitud de viaje creada');

            return $trip;
        });

        return to_route('trips.show', $trip)->with('success', 'Solicitud enviada. Queda pendiente de autorización.');
    }

    /* ------------------------------ Detalle ----------------------------- */

    public function show(Request $request, Trip $trip): Response
    {
        $user = $request->user();
        $canManage = $user->can('manage-trips');
        $isDriver = $user->driver && (int) $trip->driver_id === $user->driver->id;
        $isRequester = (int) $trip->requested_by === $user->id;

        abort_unless($canManage || $isDriver || $isRequester, 403);

        $trip->load([
            'vehicle.brand:id,name',
            'vehicle.vehicleModel:id,name',
            'driver',
            'requester:id,name',
            'authorizer:id,name',
            'logs' => fn ($q) => $q->with('user:id,name')->latest('logged_at')->latest('id'),
        ]);

        $assigning = $canManage && $trip->status === TripStatus::Requested;

        return Inertia::render('traslados/Show', [
            'trip' => $trip,
            'can' => [
                'approve' => $assigning,
                'start' => ($canManage || $isDriver) && $trip->status === TripStatus::Authorized,
                'complete' => ($canManage || $isDriver) && $trip->status === TripStatus::InProgress,
                'cancel' => ($canManage || $isRequester) && in_array($trip->status, [TripStatus::Requested, TripStatus::Authorized], true),
                'log' => ($canManage || $isDriver) && $trip->status === TripStatus::InProgress,
            ],
            'vehicles' => fn () => $assigning
                ? Vehicle::active()->with(['brand:id,name', 'vehicleModel:id,name'])->orderBy('plate')->get()
                : [],
            'drivers' => fn () => $assigning
                ? Driver::active()->orderBy('full_name')->get(['id', 'full_name', 'rut', 'is_on_duty'])
                : [],
        ]);
    }

    /* ---------------------------- Transiciones -------------------------- */

    public function approve(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('manage-trips');

        $data = $request->validate([
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->where('is_active', true)],
            'driver_id' => ['required', Rule::exists('drivers', 'id')->where('is_active', true)],
        ]);

        $trip->approve($request->user(), (int) $data['vehicle_id'], (int) $data['driver_id']);

        return back()->with('success', 'Viaje autorizado y asignado.');
    }

    public function start(Request $request, Trip $trip): RedirectResponse
    {
        $this->ensureCanOperate($request, $trip);

        $data = $request->validate(['initial_mileage_km' => ['required', 'integer', 'min:0']]);

        $trip->load('vehicle')->start($request->user(), (int) $data['initial_mileage_km']);

        return back()->with('success', 'Viaje iniciado.');
    }

    public function complete(Request $request, Trip $trip): RedirectResponse
    {
        $this->ensureCanOperate($request, $trip);

        $data = $request->validate([
            'final_mileage_km' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $trip->complete($request->user(), (int) $data['final_mileage_km'], $data['notes'] ?? null);

        return back()->with('success', 'Viaje finalizado y registrado en la bitácora.');
    }

    public function cancel(Request $request, Trip $trip): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('manage-traslados') || (int) $trip->requested_by === $user->id, 403);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $trip->cancel($user, $data['reason']);

        return back()->with('success', 'Viaje cancelado.');
    }

    private function ensureCanOperate(Request $request, Trip $trip): void
    {
        $user = $request->user();

        abort_unless(
            $user->can('manage-traslados') || ($user->driver && (int) $trip->driver_id === $user->driver->id),
            403
        );
    }
}
