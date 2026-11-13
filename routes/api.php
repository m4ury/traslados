<?php
// routes/api.php
Route::get('/usuarios-disponibles-conductores', function () {
    return response()->json([
        'data' => User::query()
            ->whereDoesntHave('conductor')
            ->orderBy('name')
            ->get(['id', 'name', 'email']),
    ]);
})->middleware(['auth:sanctum', 'can:manage-catalogs']);
