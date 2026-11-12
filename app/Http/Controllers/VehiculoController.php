<?php

namespace App\Http\Controllers;

use App\Enums\EstadoTraslado;
use App\Models\Marca;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VehiculoController extends Controller
{
    public function index(Request $request): Response
    {
        $vehiculos = Vehiculo::query()
            ->with(['marca:id,name', 'modelo:id,name'])
            ->withExists(['traslados as in_trip' => fn ($q) => $q->where('estado', EstadoTraslado::EnCurso)])
            ->when($request->search, fn ($q, $s) => $q->where('patente', 'like', '%'.strtoupper($s).'%'))
            ->when($request->marca_id, fn ($q, $id) => $q->where('marca_id', $id))
            ->when($request->estado === 'disponible', fn ($q) => $q->available())
            ->when($request->estado === 'in_trip', fn ($q) => $q->whereHas('traslados', fn ($t) => $t->where('estado', EstadoTraslado::EnCurso)))
            ->when($request->estado === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('patente')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('vehiculos/Index', [
            'vehiculos' => $vehiculos,
            'marcas' => Marca::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search', 'marca_id', 'status'),
        ]);
    }

    public function create(): Response
    {
        return $this->form();
    }

    public function edit(Vehicle $vehicle): Response
    {
        return $this->form($vehicle);
    }

    public function store(Request $request): RedirectResponse
    {
        Vehicle::create($this->validated($request));

        return to_route('vehicles.index')->with('success', 'Vehículo registrado.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->validated($request, $vehicle));

        return to_route('vehicles.index')->with('success', 'Vehículo actualizado.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        // Si tiene historial de viajes no se borra: se desactiva para no perder la bitácora.
        if ($vehicle->trips()->exists()) {
            $vehicle->update(['is_active' => false]);

            return back()->with('success', 'El vehículo tiene viajes registrados, por lo que fue desactivado.');
        }

        $vehicle->delete();

        return back()->with('success', 'Vehículo eliminado.');
    }

    private function form(?Vehicle $vehicle = null): Response
    {
        return Inertia::render('vehicles/Form', [
            'vehicle' => $vehicle,
            'brands' => Brand::query()
                ->where('is_active', true)
                ->when($vehicle, fn ($q) => $q->orWhere('id', $vehicle->brand_id))
                ->with(['vehicleModels' => fn ($q) => $q->select('id', 'brand_id', 'name')->orderBy('name')])
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => Vehicle::TYPES,
        ]);
    }

    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        $request->merge(['plate' => strtoupper(preg_replace('/[\s\.-]+/', '', (string) $request->plate))]);

        return $request->validate([
            'plate' => ['required', 'string', 'max:10', Rule::unique('vehicles', 'plate')->ignore($vehicle)],
            'brand_id' => ['required', 'exists:brands,id'],
            'vehicle_model_id' => [
                'required',
                Rule::exists('vehicle_models', 'id')->where('brand_id', $request->brand_id),
            ],
            'year' => ['required', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
            'vehicle_type' => ['required', Rule::in(Vehicle::TYPES)],
            'current_mileage_km' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ], [
            'vehicle_model_id.exists' => 'El modelo no corresponde a la marca seleccionada.',
        ]);
    }
}
