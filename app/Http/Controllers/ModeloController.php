<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use App\Models\Modelo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ModeloController extends Controller
{
    public function index(Request $request): Response
    {
        $modelo = Modelo::query()
            ->with('marca:id,name')
            ->withCount('vehiculos')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->marca_id, fn ($q, $id) => $q->where('marca_id', $id))
            ->orderBy(Marca::select('name')->whereColumn('marcas.id', 'modelos.marca_id'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('modelos/Index', [
            'modelos' => $modelo,
            'marcas' => Marca::orderBy('name')->get(['id', 'name', 'is_active']),
            'filters' => $request->only('search', 'marca_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Modelo::create($this->validated($request));

        return back()->with('success', 'Modelo creado correctamente.');
    }

    public function update(Request $request, Modelo $modelo): RedirectResponse
    {
        $modelo->update($this->validated($request, $modelo));

        return back()->with('success', 'Modelo actualizado.');
    }

    public function destroy(Modelo $modelo): RedirectResponse
    {
        if ($modelo->vehiculos()->exists()) {
            return back()->with('error', 'No se puede eliminar: el modelo tiene vehículos asociados. Puedes desactivarlo.');
        }

        $modelo->delete();

        return back()->with('success', 'Modelo eliminado.');
    }

    private function validated(Request $request, ?Modelo $modelo = null): array
    {
        return $request->validate([
            'marca_id' => ['required', 'exists:marcas,id'],
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('modelos', 'name')
                    ->where('marca_id', $request->marca_id)
                    ->ignore($modelo),
            ],
            'is_active' => ['boolean'],
        ], [
            'name.unique' => 'Ya existe un modelo con ese nombre para la marca seleccionada.',
        ]);
    }
}
