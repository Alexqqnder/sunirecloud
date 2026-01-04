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
        Schema::create('voided_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique()->comment('Código único del motivo');
            $table->string('nombre', 100)->comment('Nombre corto del motivo');
            $table->text('descripcion')->comment('Descripción detallada del motivo');
            $table->text('ejemplo')->nullable()->comment('Ejemplo de cómo redactar el motivo');
            $table->string('categoria', 50)->comment('Categoría: ERROR_DATOS, ERROR_CALCULO, OPERACION, etc.');
            $table->boolean('requiere_justificacion')->default(false)->comment('Si requiere justificación adicional');
            $table->boolean('activo')->default(true)->comment('Si el motivo está activo');
            $table->integer('orden')->default(0)->comment('Orden de visualización');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voided_reasons');
    }
};
