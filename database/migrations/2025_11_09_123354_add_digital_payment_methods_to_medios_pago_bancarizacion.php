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
        // Insertar medios de pago digitales y billeteras móviles bancarias
        DB::table('medios_pago_bancarizacion')->insert([
            // Billeteras digitales bancarias
            [
                'codigo' => 'YAPE',
                'descripcion' => 'Yape (BCP)',
                'requiere_numero_operacion' => true,
                'requiere_banco' => false,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Billetera digital del Banco de Crédito del Perú. Válida para bancarización según Ley 28194.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'PLIN',
                'descripcion' => 'Plin (Consorcio de bancos)',
                'requiere_numero_operacion' => true,
                'requiere_banco' => false,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Billetera digital multi-banco (BBVA, Interbank, Scotiabank, etc.). Válida para bancarización.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'TUNK',
                'descripcion' => 'Tunki (Scotiabank)',
                'requiere_numero_operacion' => true,
                'requiere_banco' => false,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Billetera digital de Scotiabank. Válida para bancarización según Ley 28194.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'LUKI',
                'descripcion' => 'Lukita (Caja Arequipa)',
                'requiere_numero_operacion' => true,
                'requiere_banco' => false,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Billetera digital de Caja Arequipa. Válida para bancarización.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'AGORA',
                'descripcion' => 'Agora Pay (Interbank)',
                'requiere_numero_operacion' => true,
                'requiere_banco' => false,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Billetera digital de Interbank. Válida para bancarización.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'BIM',
                'descripcion' => 'BIM - Billetera Móvil (ASBANC)',
                'requiere_numero_operacion' => true,
                'requiere_banco' => false,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Billetera móvil interbancaria respaldada por ASBANC. Válida para bancarización.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Otros medios de pago electrónicos
            [
                'codigo' => 'POS',
                'descripcion' => 'Pago con POS (Punto de Venta)',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Pago mediante terminal punto de venta. Incluye tarjetas de débito y crédito.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'QR',
                'descripcion' => 'Pago QR Bancario',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Pago mediante código QR generado por banco o entidad financiera.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'BANCA_WEB',
                'descripcion' => 'Banca por Internet',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Pago realizado mediante plataforma web del banco.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'BANCA_APP',
                'descripcion' => 'App Móvil Bancaria',
                'requiere_numero_operacion' => true,
                'requiere_banco' => true,
                'requiere_fecha' => true,
                'activo' => true,
                'observaciones' => 'Pago mediante aplicación móvil del banco (no billetera digital).',
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
        // Eliminar los medios de pago digitales agregados
        DB::table('medios_pago_bancarizacion')
            ->whereIn('codigo', ['YAPE', 'PLIN', 'TUNK', 'LUKI', 'AGORA', 'BIM', 'POS', 'QR', 'BANCA_WEB', 'BANCA_APP'])
            ->delete();
    }
};
