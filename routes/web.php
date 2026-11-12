<?php

use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ModeloController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::middleware(['auth', 'verified'])->group(function () {
        // Catálogos (solo supervisores/administradores)
        Route::middleware('can:manage-traslados')->group(function () {

            Route::resource('marcas', MarcaController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::resource('modelos', ModeloController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->parameters(['modelos' => 'modelo']);
                
            Route::resource('vehiculos', VehiculoController::class)->except(['show']);
        });

        // Traslados
        Route::prefix('traslados')->name('traslados.')->controller(TrasladoController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('spontaneous/create', 'createSpontaneous')->name('spontaneous.create');
            Route::post('spontaneous', 'storeSpontaneous')->name('spontaneous.store');
            Route::get('request/create', 'createRequest')->name('request.create');
            Route::post('request', 'storeRequest')->name('request.store');
            Route::get('{traslado}', 'show')->name('show');
            Route::patch('{traslado}/authorize', 'approve')->name('approve');
            Route::patch('{traslado}/start', 'start')->name('start');
            Route::patch('{traslado}/complete', 'complete')->name('complete');
            Route::patch('{traslado}/cancel', 'cancel')->name('cancel');
        });

        Route::post('bitacora/{traslado}/logs', [BitacoraController::class, 'store'])->name('bitacora.logs.store');

        // Bitácora / estadísticas
        Route::get('logbook', [LogbookController::class, 'index'])->name('logbook.index');
    });

});

require __DIR__.'/settings.php';
