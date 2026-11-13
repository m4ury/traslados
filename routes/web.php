<?php

use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ModeloController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\BitacoraController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrasladoController;



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
            Route::get('{traslados}', 'show')->name('show');
            Route::patch('{traslados}/authorize', 'approve')->name('approve');
            Route::patch('{traslados}/start', 'start')->name('start');
            Route::patch('{traslados}/complete', 'complete')->name('complete');
            Route::patch('{traslados}/cancel', 'cancel')->name('cancel');
        });

        Route::post('bitacora/{traslado}/logs', [BitacoraController::class, 'store'])->name('bitacora.logs.store');

        // Bitácora / estadísticas
        Route::get('logbook', [LogbookController::class, 'index'])->name('logbook.index');
    });

});

require __DIR__.'/settings.php';
