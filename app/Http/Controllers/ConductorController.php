<?php

namespace App\Http\Controllers;

use App\Models\Conductor;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;

class ConductorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'data' => Conductor::with('user:id,name,email,role')->get(),
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('conductores', 'user_id'),
            ],
            'license_number' => ['required', 'string', 'max:255', 'unique:conductores,license_number'],
            'tipo_licencia' => ['nullable', 'string', 'max:10'],
            'activo' => ['boolean'],
        ]);

        $conductor = Conductor::create($validated);

        return response()->json([
            'message' => 'Usuario asignado como conductor.',
            'data' => $conductor->load('user:id,name,email,role'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Conductor $conductor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Conductor $conductor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Conductor $conductor)
    {
        $validated = $request->validate([
            'license_number' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('conductores', 'license_number')->ignore($conductor->id),
            ],
            'tipo_licencia' => ['nullable', 'string', 'max:10'],
            'activo' => ['boolean'],
        ]);

        $conductor->update($validated);

        return response()->json([
            'message' => 'Conductor actualizado.',
            'data' => $conductor->load('user:id,name,email,role'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Conductor $conductor)
    {
        $conductor->delete();

        return response()->json([
            'message' => 'Usuario desasignado como conductor.',
        ]);
    }
}
