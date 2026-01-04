<?php

namespace App\Http\Requests\Boleta;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\BancarizacionService;

class StoreBoletaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'serie' => 'required|string|max:4',
            'fecha_emision' => 'required|date',
            'ubl_version' => 'nullable|string|max:5',
            'tipo_operacion' => 'nullable|string|max:4',
            'moneda' => 'nullable|string|max:3',
            'metodo_envio' => 'required|string|in:individual,resumen_diario',
            'forma_pago_tipo' => 'nullable|string|max:20',
            'forma_pago_cuotas' => 'nullable|array',
            
            // Cliente
            'client' => 'required|array',
            'client.tipo_documento' => 'required|string|max:1',
            'client.numero_documento' => 'required|string|max:15',
            'client.razon_social' => 'required|string|max:255',
            'client.nombre_comercial' => 'nullable|string|max:255',
            'client.direccion' => 'nullable|string|max:255',
            'client.ubigeo' => 'nullable|string|max:6',
            'client.distrito' => 'nullable|string|max:100',
            'client.provincia' => 'nullable|string|max:100',
            'client.departamento' => 'nullable|string|max:100',
            'client.telefono' => 'nullable|string|max:20',
            'client.email' => 'nullable|email|max:255',
            
            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.codigo' => 'required|string|max:30',
            'detalles.*.descripcion' => 'required|string|max:255',
            'detalles.*.unidad' => 'required|string|max:3',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',

            // Precio: Se acepta MODO RETAIL (con IGV) o MODO MAYORISTA (sin IGV)
            'detalles.*.mto_precio_unitario' => 'required_without:detalles.*.mto_valor_unitario|numeric|min:0',
            'detalles.*.mto_valor_unitario' => 'required_without:detalles.*.mto_precio_unitario|numeric|min:0',

            'detalles.*.mto_valor_gratuito' => 'nullable|numeric|min:0',
            'detalles.*.porcentaje_igv' => 'required|numeric|min:0|max:100',
            'detalles.*.porcentaje_ivap' => 'nullable|numeric|min:0|max:100',
            'detalles.*.tip_afe_igv' => 'required|string|max:2',

            // ISC (Impuesto Selectivo al Consumo)
            'detalles.*.tip_sis_isc' => 'nullable|string|in:01,02,03',
            'detalles.*.porcentaje_isc' => 'nullable|numeric|min:0|max:1000',
            'detalles.*.isc' => 'nullable|numeric|min:0',

            // ICBPER (Impuesto a las Bolsas Plásticas)
            'detalles.*.icbper' => 'nullable|numeric|min:0',
            'detalles.*.factor_icbper' => 'nullable|numeric|min:0',

            // Descuentos por línea
            'detalles.*.descuentos' => 'nullable|array',
            'detalles.*.descuentos.*.monto' => 'required_with:detalles.*.descuentos|numeric|min:0',
            
            // Leyendas
            'leyendas' => 'nullable|array',
            'leyendas.*.code' => 'required|string|max:4',
            'leyendas.*.value' => 'required|string|max:255',
            
            'datos_adicionales' => 'nullable|array',
            'usuario_creacion' => 'nullable|string|max:100',

            // Bancarización (opcional, pero recomendado si aplica)
            'bancarizacion' => 'nullable|array',
            'bancarizacion.medio_pago' => 'required_with:bancarizacion|string|max:50',
            'bancarizacion.numero_operacion' => 'nullable|string|max:100',
            'bancarizacion.fecha_pago' => 'nullable|date',
            'bancarizacion.banco' => 'nullable|string|max:100',
            'bancarizacion.observaciones' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que la sucursal pertenece a la empresa
            $branch = Branch::where('id', $this->input('branch_id'))
                          ->where('company_id', $this->input('company_id'))
                          ->first();

            if (!$branch) {
                $validator->errors()->add('branch_id', 'La sucursal no pertenece a la empresa seleccionada.');
            }

            // Calcular monto total estimado para validar bancarización y DNI
            $montoEstimado = $this->calcularMontoEstimado();
            $moneda = $this->input('moneda', 'PEN');

            // VALIDACIÓN 1: DNI OBLIGATORIO PARA MONTOS > S/ 700
            $this->validarDniObligatorio($validator, $montoEstimado, $moneda);

            // VALIDACIÓN 2: BANCARIZACIÓN
            $bancarizacionService = app(BancarizacionService::class);
            $aplicaBancarizacion = $bancarizacionService->aplicaBancarizacion($montoEstimado, $moneda);

            // Si aplica bancarización, validar que se proporcionen los datos
            if ($aplicaBancarizacion && !$this->has('bancarizacion')) {
                $umbral = $bancarizacionService->getUmbral($moneda);
                $simbolo = $moneda === 'PEN' ? 'S/' : 'US$';

                $validator->errors()->add('bancarizacion',
                    "⚠️ BANCARIZACIÓN OBLIGATORIA: Esta operación supera el umbral de {$simbolo} " . number_format($umbral, 2) .
                    " (Monto estimado: {$simbolo} " . number_format($montoEstimado, 2) . "). " .
                    "Según la Ley N° 28194, debe proporcionar los datos del medio de pago bancario. " .
                    "Sin este dato, el gasto NO será deducible para Impuesto a la Renta y NO otorgará crédito fiscal de IGV."
                );
            }

            // Validar datos de bancarización si se proporcionaron
            if ($this->has('bancarizacion')) {
                $validacion = $bancarizacionService->validarDatosBancarizacion($this->input('bancarizacion'));

                if (!$validacion['valido']) {
                    foreach ($validacion['errores'] as $error) {
                        $validator->errors()->add('bancarizacion', $error);
                    }
                }
            }
        });
    }

    /**
     * Validar DNI obligatorio para boletas > S/ 700
     * Según normativa SUNAT RS 0120-2017/SUNAT
     */
    private function validarDniObligatorio($validator, float $montoEstimado, string $moneda): void
    {
        // Solo aplica para moneda nacional (PEN)
        if ($moneda !== 'PEN') {
            return;
        }

        // Umbral de S/ 700 según normativa SUNAT
        $umbralDni = 700.00;

        // Si el monto supera S/ 700
        if ($montoEstimado > $umbralDni) {
            $tipoDocumento = $this->input('client.tipo_documento');
            $numeroDocumento = $this->input('client.numero_documento');

            // Validar que NO sea DNI genérico (99999999, 00000000, 11111111, etc.)
            $dnisGenericos = [
                '99999999',
                '00000000',
                '11111111',
                '22222222',
                '33333333',
                '44444444',
                '55555555',
                '66666666',
                '77777777',
                '88888888',
                '12345678'
            ];

            // Si es tipo DNI (1) pero usa DNI genérico
            if ($tipoDocumento === '1' && in_array($numeroDocumento, $dnisGenericos)) {
                $validator->errors()->add('client.numero_documento',
                    "⚠️ DNI OBLIGATORIO: Para boletas con monto superior a S/ 700.00 es OBLIGATORIO " .
                    "registrar el DNI REAL del cliente (Monto: S/ " . number_format($montoEstimado, 2) . "). " .
                    "No se permite el uso de DNI genérico (99999999) según la Resolución de Superintendencia " .
                    "N° 0120-2017/SUNAT. El DNI debe ser válido y corresponder al cliente."
                );
            }

            // Si NO es tipo DNI pero el monto supera S/ 700 (advertencia informativa)
            if ($tipoDocumento !== '1' && $tipoDocumento !== '6') {
                $validator->errors()->add('client.tipo_documento',
                    "⚠️ ADVERTENCIA: Para boletas con monto superior a S/ 700.00, se recomienda usar " .
                    "DNI (tipo 1) o RUC (tipo 6) del cliente real. Tipo actual: {$tipoDocumento}. " .
                    "Monto: S/ " . number_format($montoEstimado, 2) . "."
                );
            }
        }
    }

    /**
     * Calcular monto estimado de la boleta para validación
     */
    private function calcularMontoEstimado(): float
    {
        $detalles = $this->input('detalles', []);
        $total = 0;

        foreach ($detalles as $detalle) {
            $cantidad = $detalle['cantidad'] ?? 0;
            $valorUnitario = $detalle['mto_valor_unitario'] ?? 0;
            $tipAfeIgv = $detalle['tip_afe_igv'] ?? '10';
            $porcentajeIgv = $detalle['porcentaje_igv'] ?? 18;

            $subtotal = $cantidad * $valorUnitario;

            // Si es gravado, agregar IGV
            if ($tipAfeIgv === '10') {
                $subtotal = $subtotal * (1 + ($porcentajeIgv / 100));
            }

            $total += $subtotal;
        }

        return $total;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'branch_id.required' => 'La sucursal es requerida.',
            'branch_id.exists' => 'La sucursal seleccionada no existe.',
            'serie.required' => 'La serie es requerida.',
            'serie.max' => 'La serie no puede tener más de 4 caracteres.',
            'fecha_emision.required' => 'La fecha de emisión es requerida.',
            'fecha_emision.date' => 'La fecha de emisión debe ser una fecha válida.',
            'metodo_envio.required' => 'El método de envío es requerido.',
            'metodo_envio.in' => 'El método de envío debe ser individual o resumen_diario.',
            
            'client.required' => 'Los datos del cliente son requeridos.',
            'client.tipo_documento.required' => 'El tipo de documento del cliente es requerido.',
            'client.numero_documento.required' => 'El número de documento del cliente es requerido.',
            'client.razon_social.required' => 'La razón social del cliente es requerida.',
            'client.email.email' => 'El email del cliente debe ser válido.',
            
            'detalles.required' => 'Los detalles son requeridos.',
            'detalles.min' => 'Debe incluir al menos un detalle.',
            'detalles.*.codigo.required' => 'El código del producto es requerido.',
            'detalles.*.descripcion.required' => 'La descripción del producto es requerida.',
            'detalles.*.unidad.required' => 'La unidad del producto es requerida.',
            'detalles.*.cantidad.required' => 'La cantidad del producto es requerida.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'detalles.*.mto_valor_unitario.required' => 'El valor unitario es requerido.',
            'detalles.*.mto_valor_unitario.min' => 'El valor unitario debe ser mayor o igual a 0.',
            'detalles.*.porcentaje_igv.required' => 'El porcentaje de IGV es requerido.',
            'detalles.*.tip_afe_igv.required' => 'El tipo de afectación IGV es requerido.',
        ];
    }
}
