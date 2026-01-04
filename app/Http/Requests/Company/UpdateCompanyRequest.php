<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta si necesitas lógica de autorización
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ruc.required' => 'El RUC es obligatorio',
            'ruc.size' => 'El RUC debe tener exactamente 11 dígitos',
            'ruc.unique' => 'El RUC ya está registrado',
            'email.email' => 'El correo debe tener un formato válido',
            'certificado_pem.file' => 'El certificado debe ser un archivo válido (.pfx, .p12, .pem, .crt, .cer)',
            'certificado_pem.max' => 'El certificado no debe exceder 2MB',
            'certificado_password.required_if' => 'La contraseña del certificado es requerida cuando se sube un certificado',
            'logo_path.mimes' => 'El logo debe estar en formato PNG o JPG',
        ];
    }

    public function rules(): array
    {
        $companyId = $this->route('company')->id ?? null;

        return [
            'ruc' => [
                'required',
                'string',
                'size:11',
                Rule::unique('companies', 'ruc')->ignore($companyId),
            ],
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'required|string|max:255',
            'ubigeo' => 'required|string|size:6',
            'distrito' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'web' => 'nullable|url|max:255',
            'usuario_sol' => 'required|string|max:50',
            'clave_sol' => 'required|string|max:100',
            'certificado_pem' => 'nullable|file|max:2048',
            'certificado_password' => 'required_if:certificado_pem,!=,null|nullable|string|max:100',
            'endpoint_beta' => 'nullable|url|max:255',
            'endpoint_produccion' => 'nullable|url|max:255',
            'modo_produccion' => 'nullable|in:true,false,1,0',
            'logo_path' => 'nullable|file|mimes:png,jpeg,jpg|max:2048',
            'activo' => 'boolean'
        ];
    }
}
