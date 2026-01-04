<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyCompleteRequest extends FormRequest
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
            // ==================== DATOS BÁSICOS ====================
            'ruc' => 'required|string|size:11|unique:companies,ruc',
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',

            // ==================== UBICACIÓN ====================
            'direccion' => 'required|string|max:255',
            'ubigeo' => 'required|string|size:6',
            'distrito' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',

            // ==================== CONTACTO PRINCIPAL ====================
            'telefono' => 'nullable|string|max:50',
            'email' => 'required|email|max:100',
            'web' => 'nullable|url|max:255',

            // ==================== CONTACTOS ADICIONALES ====================
            'telefono_2' => 'nullable|string|max:50',
            'telefono_3' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email_ventas' => 'nullable|email|max:100',
            'email_soporte' => 'nullable|email|max:100',

            // ==================== REDES SOCIALES ====================
            'facebook' => 'nullable|url|max:200',
            'instagram' => 'nullable|url|max:200',
            'twitter' => 'nullable|url|max:200',
            'linkedin' => 'nullable|url|max:200',
            'tiktok' => 'nullable|url|max:200',

            // ==================== CUENTAS BANCARIAS ====================
            'cuentas_bancarias' => 'nullable|array',
            'cuentas_bancarias.*.banco' => 'required|string|max:100',
            'cuentas_bancarias.*.moneda' => 'required|string|in:PEN,USD',
            'cuentas_bancarias.*.tipo_cuenta' => 'required|string|in:AHORROS,CORRIENTE',
            'cuentas_bancarias.*.numero' => 'required|string|max:50',
            'cuentas_bancarias.*.cci' => 'nullable|string|max:50',
            'cuentas_bancarias.*.titular' => 'nullable|string|max:200',
            'cuentas_bancarias.*.activo' => 'nullable|boolean',

            // ==================== BILLETERAS DIGITALES ====================
            'billeteras_digitales' => 'nullable|array',
            'billeteras_digitales.*.tipo' => 'required|string|in:YAPE,PLIN,TUNKI,LUKITA,AGORA,BIM',
            'billeteras_digitales.*.numero' => 'required|string|max:50',
            'billeteras_digitales.*.titular' => 'nullable|string|max:200',
            'billeteras_digitales.*.activo' => 'nullable|boolean',

            // ==================== INFORMACIÓN PDF ====================
            'mensaje_pdf' => 'nullable|string|max:500',
            'terminos_condiciones_pdf' => 'nullable|string|max:2000',
            'politica_garantia' => 'nullable|string|max:2000',

            // ==================== CONFIGURACIÓN PDF ====================
            'mostrar_cuentas_en_pdf' => 'nullable|boolean',
            'mostrar_billeteras_en_pdf' => 'nullable|boolean',
            'mostrar_redes_sociales_en_pdf' => 'nullable|boolean',
            'mostrar_contactos_adicionales_en_pdf' => 'nullable|boolean',

            // ==================== CREDENCIALES SUNAT ====================
            'usuario_sol' => 'required|string|max:50',
            'clave_sol' => 'required|string|max:100',
            'certificado_pem' => 'nullable|file|max:2048',
            'certificado_password' => 'required_if:certificado_pem,!=,null|nullable|string|max:100',

            // ==================== CREDENCIALES GRE ====================
            'gre_client_id_beta' => 'nullable|string|max:100',
            'gre_client_secret_beta' => 'nullable|string|max:100',
            'gre_client_id_produccion' => 'nullable|string|max:100',
            'gre_client_secret_produccion' => 'nullable|string|max:100',
            'gre_ruc_proveedor' => 'nullable|string|size:11',
            'gre_usuario_sol' => 'nullable|string|max:50',
            'gre_clave_sol' => 'nullable|string|max:100',

            // ==================== ENDPOINTS ====================
            'endpoint_beta' => 'nullable|url|max:255',
            'endpoint_produccion' => 'nullable|url|max:255',

            // ==================== CONFIGURACIÓN GENERAL ====================
            'modo_produccion' => 'nullable|boolean',
            'activo' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // RUC
            'ruc.required' => 'El RUC es obligatorio',
            'ruc.size' => 'El RUC debe tener exactamente 11 dígitos',
            'ruc.unique' => 'El RUC ya está registrado en el sistema',

            // Razón social
            'razon_social.required' => 'La razón social es obligatoria',
            'razon_social.max' => 'La razón social no puede exceder 255 caracteres',

            // Ubicación
            'direccion.required' => 'La dirección es obligatoria',
            'ubigeo.required' => 'El ubigeo es obligatorio',
            'ubigeo.size' => 'El ubigeo debe tener exactamente 6 dígitos',
            'distrito.required' => 'El distrito es obligatorio',
            'provincia.required' => 'La provincia es obligatoria',
            'departamento.required' => 'El departamento es obligatorio',

            // Email
            'email.required' => 'El email principal es obligatorio',
            'email.email' => 'El email principal debe ser una dirección válida',
            'email_ventas.email' => 'El email de ventas debe ser una dirección válida',
            'email_soporte.email' => 'El email de soporte debe ser una dirección válida',

            // URLs
            'web.url' => 'La URL del sitio web debe ser válida',
            'facebook.url' => 'La URL de Facebook debe ser válida',
            'instagram.url' => 'La URL de Instagram debe ser válida',
            'twitter.url' => 'La URL de Twitter debe ser válida',
            'linkedin.url' => 'La URL de LinkedIn debe ser válida',
            'tiktok.url' => 'La URL de TikTok debe ser válida',

            // Cuentas bancarias
            'cuentas_bancarias.*.banco.required' => 'El nombre del banco es requerido',
            'cuentas_bancarias.*.moneda.required' => 'La moneda es requerida',
            'cuentas_bancarias.*.moneda.in' => 'La moneda debe ser PEN o USD',
            'cuentas_bancarias.*.tipo_cuenta.required' => 'El tipo de cuenta es requerido',
            'cuentas_bancarias.*.tipo_cuenta.in' => 'El tipo de cuenta debe ser AHORROS o CORRIENTE',
            'cuentas_bancarias.*.numero.required' => 'El número de cuenta es requerido',

            // Billeteras digitales
            'billeteras_digitales.*.tipo.required' => 'El tipo de billetera es requerido',
            'billeteras_digitales.*.tipo.in' => 'El tipo de billetera debe ser YAPE, PLIN, TUNKI, LUKITA, AGORA o BIM',
            'billeteras_digitales.*.numero.required' => 'El número de la billetera es requerido',

            // Credenciales SUNAT
            'usuario_sol.required' => 'El usuario SOL es obligatorio',
            'clave_sol.required' => 'La clave SOL es obligatoria',
            'certificado_pem.file' => 'El certificado debe ser un archivo válido (.pfx, .p12, .pem, .crt, .cer)',
            'certificado_pem.max' => 'El certificado no debe exceder 2MB',
            'certificado_password.required_if' => 'La contraseña del certificado es requerida cuando se sube un certificado',

            // GRE
            'gre_ruc_proveedor.size' => 'El RUC del proveedor GRE debe tener 11 dígitos',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'ruc' => 'RUC',
            'razon_social' => 'razón social',
            'nombre_comercial' => 'nombre comercial',
            'direccion' => 'dirección',
            'ubigeo' => 'ubigeo',
            'distrito' => 'distrito',
            'provincia' => 'provincia',
            'departamento' => 'departamento',
            'telefono' => 'teléfono',
            'telefono_2' => 'teléfono 2',
            'telefono_3' => 'teléfono 3',
            'whatsapp' => 'WhatsApp',
            'email' => 'email',
            'email_ventas' => 'email de ventas',
            'email_soporte' => 'email de soporte',
            'web' => 'sitio web',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
            'usuario_sol' => 'usuario SOL',
            'clave_sol' => 'clave SOL',
            'certificado_password' => 'contraseña del certificado',
            'endpoint_beta' => 'endpoint beta',
            'endpoint_produccion' => 'endpoint producción',
            'modo_produccion' => 'modo producción',
            'activo' => 'activo',
        ];
    }
}
