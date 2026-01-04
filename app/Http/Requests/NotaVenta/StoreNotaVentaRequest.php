<?php

namespace App\Http\Requests\NotaVenta;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotaVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar según tu sistema de permisos
    }

    public function rules(): array
    {
        return [
            // Empresa y sucursal
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',

            // Cliente
            'client' => 'required|array',
            'client.tipo_documento' => 'required|string|in:1,6,0', // 1=DNI, 6=RUC, 0=Otros
            'client.numero_documento' => 'required|string|max:15',
            'client.razon_social' => 'required|string|max:255',
            'client.direccion' => 'nullable|string|max:255',
            'client.email' => 'nullable|email|max:100',
            'client.telefono' => 'nullable|string|max:20',

            // Documento
            'serie' => 'required|string|size:4', // NV01, NV02, etc.
            'fecha_emision' => 'required|date',

            // Configuración
            'ubl_version' => 'nullable|string|in:2.0,2.1',
            'moneda' => 'nullable|string|in:PEN,USD,EUR',
            'tipo_operacion' => 'nullable|string|max:4',

            // Detalles (items)
            'detalles' => 'required|array|min:1',
            'detalles.*.codigo' => 'nullable|string|max:50',
            'detalles.*.unidad' => 'required|string|max:10',
            'detalles.*.descripcion' => 'required|string|max:500',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.codigo_afectacion_igv' => 'required|string|in:10,20,30,40', // 10=Gravado, 20=Exonerado, 30=Inafecto
            'detalles.*.porcentaje_igv' => 'nullable|numeric|min:0|max:100',
            'detalles.*.descuento' => 'nullable|numeric|min:0',

            // Opcionales
            'leyendas' => 'nullable|array',
            'leyendas.*.code' => 'required_with:leyendas|string|max:10',
            'leyendas.*.value' => 'required_with:leyendas|string',

            'datos_adicionales' => 'nullable|array',
            'observaciones' => 'nullable|string|max:1000',
            'usuario_creacion' => 'nullable|string|max:100',

            // Descuentos y cargos globales
            'descuentos' => 'nullable|array',
            'cargos' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es obligatoria',
            'company_id.exists' => 'La empresa seleccionada no existe',
            'branch_id.required' => 'La sucursal es obligatoria',
            'branch_id.exists' => 'La sucursal seleccionada no existe',

            'client.required' => 'Los datos del cliente son obligatorios',
            'client.tipo_documento.required' => 'El tipo de documento del cliente es obligatorio',
            'client.numero_documento.required' => 'El número de documento del cliente es obligatorio',
            'client.razon_social.required' => 'La razón social del cliente es obligatoria',

            'serie.required' => 'La serie es obligatoria',
            'serie.size' => 'La serie debe tener exactamente 4 caracteres',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria',

            'detalles.required' => 'Debe incluir al menos un item',
            'detalles.min' => 'Debe incluir al menos un item',
            'detalles.*.descripcion.required' => 'La descripción del item es obligatoria',
            'detalles.*.cantidad.required' => 'La cantidad del item es obligatoria',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio',
            'detalles.*.codigo_afectacion_igv.required' => 'El código de afectación IGV es obligatorio',
        ];
    }
}
