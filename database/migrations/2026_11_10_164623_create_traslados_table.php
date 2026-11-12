<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('traslados', function (Blueprint $table) {

            /*

            passengers_count

            distance_km
            */
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('conductor_id')->constrained('conductores')->restrictOnDelete();

            $table->foreignId('solicitado_por')->nulable()->constrained('users')->restrictOnDelete();
            $table->foreignId('autorizado_por')->nulable()->constrained('users')->restrictOnDelete();

            $table->dateTime('salida_at');
            $table->dateTime('regreso_at')->nullable();
            $table->dateTime('programado_at')->nullable();

            $table->string('origen');
            $table->string('destino');

            $table->string('proposito');
            $table->unsignedSmallInteger('cantidad_pasajeros');

            $table->unsignedInteger('init_km')->nullable();
            $table->unsignedInteger('final_km')->nullable();
            $table->unsignedInteger('distancia_km')->nullable();

            $table->string('tipo_traslado', 30)->nullable();
            $table->string('estado', 30)->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehiculo_id', 'salida_at']);
            $table->index(['conductor_id', 'salida_at']);

            $table->index(['estado', 'programado_at']);
            $table->index(['vehiculo_id', 'estado']);
            $table->index(['conductor_id', 'estado']);
            $table->index(['tipo_traslado', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traslados');
    }
};
