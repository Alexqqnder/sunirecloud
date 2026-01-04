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
        Schema::table('boletas', function (Blueprint $table) {
            $table->enum('estado_anulacion', ['sin_anular', 'pendiente_anulacion', 'anulada'])
                ->default('sin_anular')
                ->after('estado_sunat')
                ->comment('Estado de anulación: sin_anular, pendiente_anulacion (esperando resumen), anulada (confirmada por SUNAT)');

            $table->string('motivo_anulacion', 100)
                ->nullable()
                ->after('estado_anulacion')
                ->comment('Motivo de la anulación oficial');

            $table->timestamp('fecha_solicitud_anulacion')
                ->nullable()
                ->after('motivo_anulacion')
                ->comment('Fecha en que se solicitó la anulación');

            $table->unsignedBigInteger('usuario_solicitud_anulacion_id')
                ->nullable()
                ->after('fecha_solicitud_anulacion')
                ->comment('ID del usuario que solicitó la anulación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boletas', function (Blueprint $table) {
            $table->dropColumn([
                'estado_anulacion',
                'motivo_anulacion',
                'fecha_solicitud_anulacion',
                'usuario_solicitud_anulacion_id'
            ]);
        });
    }
};
