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
        Schema::table('companies', function (Blueprint $table) {
            // Información adicional para mostrar en PDFs

            // Teléfonos adicionales
            $table->string('telefono_2', 50)->nullable()->after('telefono')
                ->comment('Segundo teléfono de contacto');
            $table->string('telefono_3', 50)->nullable()->after('telefono_2')
                ->comment('Tercer teléfono de contacto');
            $table->string('whatsapp', 50)->nullable()->after('telefono_3')
                ->comment('Número de WhatsApp');

            // Emails adicionales
            $table->string('email_ventas', 100)->nullable()->after('email')
                ->comment('Email de ventas');
            $table->string('email_soporte', 100)->nullable()->after('email_ventas')
                ->comment('Email de soporte');

            // Redes sociales
            $table->string('facebook', 200)->nullable()->after('web')
                ->comment('URL de Facebook');
            $table->string('instagram', 200)->nullable()->after('facebook')
                ->comment('URL de Instagram');
            $table->string('twitter', 200)->nullable()->after('instagram')
                ->comment('URL de Twitter/X');
            $table->string('linkedin', 200)->nullable()->after('twitter')
                ->comment('URL de LinkedIn');
            $table->string('tiktok', 200)->nullable()->after('linkedin')
                ->comment('URL de TikTok');

            // Cuentas bancarias (JSON array)
            $table->json('cuentas_bancarias')->nullable()->after('tiktok')
                ->comment('Array de cuentas bancarias: [{"banco": "BCP", "moneda": "PEN", "numero": "xxx", "cci": "xxx"}]');

            // Billeteras digitales (JSON array)
            $table->json('billeteras_digitales')->nullable()->after('cuentas_bancarias')
                ->comment('Array de billeteras: [{"tipo": "YAPE", "numero": "999999999", "titular": "Nombre"}]');

            // Información adicional para PDF
            $table->text('mensaje_pdf')->nullable()->after('billeteras_digitales')
                ->comment('Mensaje personalizado para mostrar en PDFs (ej: "Gracias por su compra")');
            $table->text('terminos_condiciones_pdf')->nullable()->after('mensaje_pdf')
                ->comment('Términos y condiciones a mostrar en PDFs');
            $table->text('politica_garantia')->nullable()->after('terminos_condiciones_pdf')
                ->comment('Política de garantía/devolución');

            // Configuración de visualización en PDF
            $table->boolean('mostrar_cuentas_en_pdf')->default(true)->after('politica_garantia')
                ->comment('Mostrar cuentas bancarias en PDF');
            $table->boolean('mostrar_billeteras_en_pdf')->default(true)->after('mostrar_cuentas_en_pdf')
                ->comment('Mostrar billeteras digitales en PDF');
            $table->boolean('mostrar_redes_sociales_en_pdf')->default(false)->after('mostrar_billeteras_en_pdf')
                ->comment('Mostrar redes sociales en PDF');
            $table->boolean('mostrar_contactos_adicionales_en_pdf')->default(true)->after('mostrar_redes_sociales_en_pdf')
                ->comment('Mostrar teléfonos y emails adicionales en PDF');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'telefono_2',
                'telefono_3',
                'whatsapp',
                'email_ventas',
                'email_soporte',
                'facebook',
                'instagram',
                'twitter',
                'linkedin',
                'tiktok',
                'cuentas_bancarias',
                'billeteras_digitales',
                'mensaje_pdf',
                'terminos_condiciones_pdf',
                'politica_garantia',
                'mostrar_cuentas_en_pdf',
                'mostrar_billeteras_en_pdf',
                'mostrar_redes_sociales_en_pdf',
                'mostrar_contactos_adicionales_en_pdf',
            ]);
        });
    }
};
