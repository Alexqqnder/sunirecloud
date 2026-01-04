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
        Schema::table('invoices', function (Blueprint $table) {
            // Bancarización - Ley N° 28194
            $table->boolean('bancarizacion_aplica')->default(false)->after('estado_sunat')
                ->comment('Indica si la operación está sujeta a bancarización (>S/2000 o >US$500)');

            $table->decimal('bancarizacion_monto_umbral', 12, 2)->nullable()->after('bancarizacion_aplica')
                ->comment('Umbral de bancarización aplicable según moneda (2000 PEN / 500 USD)');

            $table->string('bancarizacion_medio_pago', 50)->nullable()->after('bancarizacion_monto_umbral')
                ->comment('Medio de pago: transferencia, tarjeta_debito, tarjeta_credito, cheque, etc.');

            $table->string('bancarizacion_numero_operacion', 100)->nullable()->after('bancarizacion_medio_pago')
                ->comment('Número de operación bancaria, referencia o voucher');

            $table->date('bancarizacion_fecha_pago')->nullable()->after('bancarizacion_numero_operacion')
                ->comment('Fecha en que se realizó el pago bancario');

            $table->string('bancarizacion_banco', 100)->nullable()->after('bancarizacion_fecha_pago')
                ->comment('Nombre del banco o entidad financiera');

            $table->boolean('bancarizacion_validado')->default(false)->after('bancarizacion_banco')
                ->comment('Indica si se ha validado el cumplimiento de bancarización');

            $table->text('bancarizacion_observaciones')->nullable()->after('bancarizacion_validado')
                ->comment('Observaciones adicionales sobre el pago o validación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'bancarizacion_aplica',
                'bancarizacion_monto_umbral',
                'bancarizacion_medio_pago',
                'bancarizacion_numero_operacion',
                'bancarizacion_fecha_pago',
                'bancarizacion_banco',
                'bancarizacion_validado',
                'bancarizacion_observaciones'
            ]);
        });
    }
};
