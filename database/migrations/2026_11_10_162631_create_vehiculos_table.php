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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('patente', 20)->unique();
            $table->string('vin', 32)->nullable()->unique();
            $table->string('numero_motor', 60)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('numero_motor', 60)->nullable();
            $table->string('transmision', 30)->nullable();
            $table->foreignId('modelo_id')->constrained('modelos');
            $table->foreignId('marca_id')->constrained('marcas');
            $table->unsignedSmallInteger('anio');
            $table->unsignedInteger('actual_km')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['marca_id', 'modelo_id', 'anio']);
            $table->index('placa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
