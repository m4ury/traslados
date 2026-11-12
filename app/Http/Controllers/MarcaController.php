<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $marcas = Marca::query()
            ->withCount('modelos')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('marcas/Index', [
            'marcas' => $marcas,
            'filters' => $request->only('search'),
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Marca::create($this->validated($request));

        return back()->with('success', 'Marca creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Marca $marca)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Marca $marca)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Marca $marca): RedirectResponse
    {
        $marca->update($this->validated($request, $marca));

        return back()->with('success', 'Marca actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Marca $marca): RedirectResponse
    {
        if ($marca->vehiculos()->exists() || $marca->modelos()->exists()) {
            return back()->with('error', 'No se puede eliminar: la marca tiene modelos o vehículos asociados. Puedes desactivarla.');
        }

        $marca->delete();

        return back()->with('success', 'Marca eliminada.');
    }

    private function validated(Request $request, ?Marca $marca = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('marcas', 'name')->ignore($marca)],
            'is_active' => ['boolean'],
        ]);
    }
}
