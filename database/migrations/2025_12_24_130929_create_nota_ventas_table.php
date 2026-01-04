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
        Schema::create('nota_ventas', function (Blueprint $table) {
            $table->id();

            // ============================================
            // RELACIONES
            // ============================================
            $table->foreignId('company_id')
                  ->constrained('companies')
                  ->onDelete('cascade');

            $table->foreignId('branch_id')
                  ->constrained('branches')
                  ->onDelete('cascade');

            $table->foreignId('client_id')
                  ->nullable()
                  ->constrained('clients')
                  ->onDelete('set null');

            // ============================================
            // IDENTIFICACIÓN DEL DOCUMENTO
            // ============================================
            $table->string('tipo_documento', 2)->default('17'); // Código Nota de Venta
            $table->string('serie', 4); // NV01, NV02, etc.
            $table->string('correlativo', 8);
            $table->string('numero_completo', 13)->unique(); // NV01-00000001

            // ============================================
            // FECHAS
            // ============================================
            $table->date('fecha_emision');

            // ============================================
            // CONFIGURACIÓN
            // ============================================
            $table->string('ubl_version', 5)->default('2.1');
            $table->string('moneda', 3)->default('PEN');
            $table->string('tipo_operacion', 4)->default('0101');

            // ============================================
            // MONTOS FINANCIEROS
            // ============================================
            $table->decimal('valor_venta', 12, 2)->default(0);
            $table->decimal('mto_oper_gravadas', 12, 2)->default(0);
            $table->decimal('mto_oper_exoneradas', 12, 2)->default(0);
            $table->decimal('mto_oper_inafectas', 12, 2)->default(0);
            $table->decimal('mto_igv', 12, 2)->default(0);
            $table->decimal('mto_isc', 12, 2)->default(0);
            $table->decimal('total_impuestos', 12, 2)->default(0);
            $table->decimal('mto_imp_venta', 12, 2); // Total a pagar

            // Descuentos y cargos opcionales
            $table->decimal('mto_descuentos', 12, 2)->default(0);
            $table->decimal('mto_cargos', 12, 2)->default(0);

            // ============================================
            // DATOS ESTRUCTURADOS (JSON)
            // ============================================
            $table->json('detalles'); // Array de items
            $table->json('leyendas')->nullable(); // Leyendas y observaciones
            $table->json('datos_adicionales')->nullable(); // Info adicional

            // ============================================
            // ARCHIVOS (SOLO PDF - NO XML NI CDR)
            // ============================================
            $table->string('pdf_path')->nullable();

            // ============================================
            // OPCIONALES
            // ============================================
            $table->string('codigo_hash')->nullable(); // Hash interno (opcional)
            $table->string('usuario_creacion')->nullable();
            $table->text('observaciones')->nullable();

            // ============================================
            // TIMESTAMPS
            // ============================================
            $table->timestamps();
            $table->softDeletes();

            // ============================================
            // ÍNDICES
            // ============================================
            $table->index('company_id');
            $table->index('branch_id');
            $table->index('client_id');
            $table->index('fecha_emision');
            $table->index(['company_id', 'fecha_emision']);
            $table->index(['branch_id', 'serie', 'correlativo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_ventas');
    }
};
