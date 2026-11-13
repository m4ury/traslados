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

    public function edit(Vehiculo $vehiculo): Response
    {
        return $this->form($vehiculo);
    }

    public function store(Request $request): RedirectResponse
    {
        Vehiculo::create($this->validated($request));

        return to_route('vehiculos.index')->with('success', 'Vehículo registrado.');
    }

    public function update(Request $request, Vehiculo $vehiculo): RedirectResponse
    {
        $vehiculo->update($this->validated($request, $vehiculo));

        return to_route('vehiculos.index')->with('success', 'Vehículo actualizado.');
    }

    public function destroy(Vehiculo $vehiculo): RedirectResponse
    {
        // Si tiene historial de viajes no se borra: se desactiva para no perder la bitácora.
        if ($vehiculo->trips()->exists()) {
            $vehiculo->update(['is_active' => false]);

            return back()->with('success', 'El vehículo tiene viajes registrados, por lo que fue desactivado.');
        }

        $vehiculo->delete();

        return back()->with('success', 'Vehículo eliminado.');
    }

    private function form(?Vehiculo $vehiculo = null): Response
    {
        return Inertia::render('vehiculos/Form', [
            'vehiculo' => $vehiculo,
            'marcas' => Marca::query()
                ->where('is_active', true)
                ->when($vehiculo, fn ($q) => $q->orWhere('id', $vehiculo->marca_id))
                ->with(['Modelos' => fn ($q) => $q->select('id', 'marca_id', 'name')->orderBy('name')])
                ->orderBy('name')
                ->get(['id', 'name']),
            //'types' => Vehiculo::TYPES,
        ]);
    }

    private function validated(Request $request, ?Vehiculo $vehiculo = null): array
    {
        $request->merge(['patente' => strtoupper(preg_replace('/[\s\.-]+/', '', (string) $request->patente))]);

        return $request->validate([
            'patente' => ['required', 'string', 'max:10', Rule::unique('vehiculos', 'patente')->ignore($vehiculo)],
            'marca_id' => ['required', 'exists:marcas,id'],
            'modelo_id' => [
                'required',
                Rule::exists('modelos', 'id')->where('marca_id', $request->marca_id),
            ],
            'anio' => ['required', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
            //'vehicle_type' => ['required', Rule::in(Vehiculo::TYPES)],
            'actual_km' => ['required', 'integer', 'min:0'],
            'activo' => ['boolean'],
        ], [
            'modelo_id.exists' => 'El modelo no corresponde a la marca seleccionada.',
        ]);
    }
}
