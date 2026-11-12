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
        Schema::create('conductores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->nullable();
            $table->string('rut')->unique();
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('license_number', 30)->nullable();
            $table->date('license_expires_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('en_turno')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conductors');
    }
};
