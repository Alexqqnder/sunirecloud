<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medios_pago_bancarizacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique()->comment('Código del medio de pago (TRAN, TDEB, TCRE, etc.)');
            $table->string('descripcion', 100)->comment('Descripción del medio de pago');
            $table->boolean('requiere_numero_operacion')->default(true)->comment('Indica si requiere número de operación');
            $table->boolean('requiere_banco')->default(true)->comment('Indica si requiere nombre del banco');
            $table->boolean('requiere_fecha')->default(true)->comment('Indica si requiere fecha de pago');
            $table->boolean('activo')->default(true)->comment('Indica si el medio de pago está activo');
            $table->text('observaciones')->nullable()->comment('Observaciones adicionales sobre el medio de pago');
            $table->timestamps();
        });

        // Insertar medios de pago válidos según Ley N° 28194
        DB::table('medios_pago_bancarizacion')->insert([
            [
                'codigo' => 'TRAN',
                'descripcion' => 'Transferencia bancaria',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Transferencia interbancaria o dentro del mismo banco',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'TDEB',
                'descripcion' => 'Tarjeta de débito',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Tarjeta de débito expedida en el país',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'TCRE',
                'descripcion' => 'Tarjeta de crédito',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Tarjeta de crédito expedida en el país',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'CHEQ',
                'descripcion' => 'Cheque no negociable',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Debe incluir cláusula "no negociable", "intransferible" o "no a la orden"',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'DEPO',
                'descripcion' => 'Depósito en cuenta',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Depósito directo en cuenta bancaria',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'GIRO',
                'descripcion' => 'Giro u orden de pago',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Giro bancario u orden de pago',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'REME',
                'descripcion' => 'Remesa',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Remesa bancaria, comúnmente para operaciones internacionales',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'CRED',
                'descripcion' => 'Carta de crédito',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Utilizada en operaciones de comercio exterior',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medios_pago_bancarizacion');
    }
};
