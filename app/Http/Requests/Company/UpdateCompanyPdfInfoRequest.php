<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyPdfInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Contactos adicionales
            'telefono_2' => 'nullable|string|max:50',
            'telefono_3' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email_ventas' => 'nullable|email|max:100',
            'email_soporte' => 'nullable|email|max:100',

            // Redes sociales
            'facebook' => 'nullable|url|max:200',
            'instagram' => 'nullable|url|max:200',
            'twitter' => 'nullable|url|max:200',
            'linkedin' => 'nullable|url|max:200',
            'tiktok' => 'nullable|url|max:200',

            // Cuentas bancarias
            'cuentas_bancarias' => 'nullable|array',
            'cuentas_bancarias.*.banco' => 'required|string|max:100',
            'cuentas_bancarias.*.moneda' => 'required|string|in:PEN,USD',
            'cuentas_bancarias.*.tipo_cuenta' => 'required|string|in:AHORROS,CORRIENTE',
            'cuentas_bancarias.*.numero' => 'required|string|max:50',
            'cuentas_bancarias.*.cci' => 'nullable|string|max:50',
            'cuentas_bancarias.*.titular' => 'nullable|string|max:200',
            'cuentas_bancarias.*.activo' => 'nullable|boolean',

            // Billeteras digitales
            'billeteras_digitales' => 'nullable|array',
            'billeteras_digitales.*.tipo' => 'required|string|in:YAPE,PLIN,TUNKI,LUKITA,AGORA,BIM',
            'billeteras_digitales.*.numero' => 'required|string|max:50',
            'billeteras_digitales.*.titular' => 'nullable|string|max:200',
            'billeteras_digitales.*.activo' => 'nullable|boolean',

            // Información adicional
            'mensaje_pdf' => 'nullable|string|max:500',
            'terminos_condiciones_pdf' => 'nullable|string|max:2000',
            'politica_garantia' => 'nullable|string|max:2000',

            // Configuración de visualización
            'mostrar_cuentas_en_pdf' => 'nullable|boolean',
            'mostrar_billeteras_en_pdf' => 'nullable|boolean',
            'mostrar_redes_sociales_en_pdf' => 'nullable|boolean',
            'mostrar_contactos_adicionales_en_pdf' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email_ventas.email' => 'El email de ventas debe ser una dirección de correo válida',
            'email_soporte.email' => 'El email de soporte debe ser una dirección de correo válida',
            'facebook.url' => 'La URL de Facebook debe ser válida',
            'instagram.url' => 'La URL de Instagram debe ser válida',
            'twitter.url' => 'La URL de Twitter debe ser válida',
            'linkedin.url' => 'La URL de LinkedIn debe ser válida',
            'tiktok.url' => 'La URL de TikTok debe ser válida',
            'cuentas_bancarias.*.banco.required' => 'El nombre del banco es requerido',
            'cuentas_bancarias.*.moneda.required' => 'La moneda es requerida',
            'cuentas_bancarias.*.moneda.in' => 'La moneda debe ser PEN o USD',
            'cuentas_bancarias.*.tipo_cuenta.required' => 'El tipo de cuenta es requerido',
            'cuentas_bancarias.*.tipo_cuenta.in' => 'El tipo de cuenta debe ser AHORROS o CORRIENTE',
            'cuentas_bancarias.*.numero.required' => 'El número de cuenta es requerido',
            'billeteras_digitales.*.tipo.required' => 'El tipo de billetera es requerido',
            'billeteras_digitales.*.tipo.in' => 'El tipo de billetera debe ser YAPE, PLIN, TUNKI, LUKITA, AGORA o BIM',
            'billeteras_digitales.*.numero.required' => 'El número de la billetera es requerido',
        ];
    }
}
