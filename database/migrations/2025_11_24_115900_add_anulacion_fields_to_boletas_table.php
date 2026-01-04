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
            $table->boolean('anulada_localmente')->default(false)->after('estado_sunat')
                ->comment('Indica si la boleta fue anulada localmente sin enviar a SUNAT');
            $table->string('motivo_anulacion_local', 100)->nullable()->after('anulada_localmente')
                ->comment('Motivo de la anulación local');
            $table->text('observaciones_anulacion')->nullable()->after('motivo_anulacion_local')
                ->comment('Observaciones detalladas de la anulación local');
            $table->timestamp('fecha_anulacion_local')->nullable()->after('observaciones_anulacion')
                ->comment('Fecha en que se anuló localmente');
            $table->unsignedBigInteger('usuario_anulacion_id')->nullable()->after('fecha_anulacion_local')
                ->comment('ID del usuario que anuló localmente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boletas', function (Blueprint $table) {
            $table->dropColumn([
                'anulada_localmente',
                'motivo_anulacion_local',
                'observaciones_anulacion',
                'fecha_anulacion_local',
                'usuario_anulacion_id'
            ]);
        });
    }
};
