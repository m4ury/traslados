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
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traslado_id')->constrained('traslados')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('registrado_at');
            $table->string('event_type', 30);
            $table->unsignedInteger('mileage_km')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['traslado_id', 'registrado_at']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
