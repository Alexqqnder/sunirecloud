<?php

namespace Database\Seeders;

use App\Models\VoidedReason;
use Illuminate\Database\Seeder;

class VoidedReasonsSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            // CATEGORÍA: ERROR_DATOS_CLIENTE
            [
                'codigo' => 'ERR_RUC',
                'nombre' => 'Error en RUC/DNI del Cliente',
                'descripcion' => 'Se emitió el comprobante con RUC o número de documento de identidad incorrecto del cliente',
                'ejemplo' => 'Error en RUC del cliente - Se consignó 20123456789 cuando el correcto es 20987654321',
                'categoria' => 'ERROR_DATOS_CLIENTE',
                'requiere_justificacion' => true,
                'orden' => 1
            ],
            [
                'codigo' => 'ERR_RAZON',
                'nombre' => 'Error en Razón Social o Nombre del Cliente',
                'descripcion' => 'Se consignó incorrectamente el nombre o razón social del cliente',
                'ejemplo' => 'Error en razón social - Se consignó "ABC SAC" cuando el correcto es "ABC EIRL"',
                'categoria' => 'ERROR_DATOS_CLIENTE',
                'requiere_justificacion' => false,
                'orden' => 2
            ],
            [
                'codigo' => 'ERR_DIR',
                'nombre' => 'Error en Dirección del Cliente',
                'descripcion' => 'Se registró incorrectamente la dirección del cliente',
                'ejemplo' => 'Error en dirección del cliente - Se consignó dirección incorrecta',
                'categoria' => 'ERROR_DATOS_CLIENTE',
                'requiere_justificacion' => false,
                'orden' => 3
            ],

            // CATEGORÍA: ERROR_DESCRIPCION
            [
                'codigo' => 'ERR_PROD_DESC',
                'nombre' => 'Error en Descripción de Productos/Servicios',
                'descripcion' => 'La descripción de los bienes o servicios es incorrecta',
                'ejemplo' => 'Error en descripción - Se consignó "Laptop HP" cuando corresponde "Tablet Samsung"',
                'categoria' => 'ERROR_DESCRIPCION',
                'requiere_justificacion' => false,
                'orden' => 4
            ],
            [
                'codigo' => 'ERR_CANT',
                'nombre' => 'Error en Cantidad',
                'descripcion' => 'Se registró una cantidad incorrecta de productos o servicios',
                'ejemplo' => 'Error en cantidad - Se consignó 10 unidades cuando corresponde 5 unidades',
                'categoria' => 'ERROR_DESCRIPCION',
                'requiere_justificacion' => false,
                'orden' => 5
            ],
            [
                'codigo' => 'ERR_UNIDAD',
                'nombre' => 'Error en Unidad de Medida',
                'descripcion' => 'Se utilizó una unidad de medida incorrecta',
                'ejemplo' => 'Error en unidad de medida - Se consignó KG cuando corresponde UND',
                'categoria' => 'ERROR_DESCRIPCION',
                'requiere_justificacion' => false,
                'orden' => 6
            ],
            [
                'codigo' => 'ERR_COD_PROD',
                'nombre' => 'Error en Código de Producto',
                'descripcion' => 'Se registró un código de producto incorrecto',
                'ejemplo' => 'Error en código de producto - Se consignó SKU-001 cuando corresponde SKU-002',
                'categoria' => 'ERROR_DESCRIPCION',
                'requiere_justificacion' => false,
                'orden' => 7
            ],

            // CATEGORÍA: ERROR_CALCULO
            [
                'codigo' => 'ERR_PRECIO',
                'nombre' => 'Error en Precio Unitario',
                'descripcion' => 'El precio unitario registrado es incorrecto',
                'ejemplo' => 'Error en precio unitario - Se consignó S/ 100.00 cuando corresponde S/ 150.00',
                'categoria' => 'ERROR_CALCULO',
                'requiere_justificacion' => false,
                'orden' => 8
            ],
            [
                'codigo' => 'ERR_IGV',
                'nombre' => 'Error en Cálculo de IGV',
                'descripcion' => 'El cálculo del IGV es incorrecto o se aplicó tasa incorrecta',
                'ejemplo' => 'Error en cálculo de IGV - Se aplicó tasa incorrecta por tipo de afectación equivocada',
                'categoria' => 'ERROR_CALCULO',
                'requiere_justificacion' => false,
                'orden' => 9
            ],
            [
                'codigo' => 'ERR_TOTAL',
                'nombre' => 'Error en Monto Total',
                'descripcion' => 'El monto total del comprobante es incorrecto',
                'ejemplo' => 'Error en monto total - Se consignó S/ 1,180.00 cuando corresponde S/ 590.00',
                'categoria' => 'ERROR_CALCULO',
                'requiere_justificacion' => false,
                'orden' => 10
            ],
            [
                'codigo' => 'ERR_TC',
                'nombre' => 'Error en Tipo de Cambio',
                'descripcion' => 'Se aplicó un tipo de cambio incorrecto en operaciones en moneda extranjera',
                'ejemplo' => 'Error en tipo de cambio - Se aplicó TC 3.70 cuando corresponde 3.75',
                'categoria' => 'ERROR_CALCULO',
                'requiere_justificacion' => false,
                'orden' => 11
            ],
            [
                'codigo' => 'ERR_DESCUENTO',
                'nombre' => 'Error en Descuentos',
                'descripcion' => 'Se aplicó un descuento incorrecto o no corresponde',
                'ejemplo' => 'Error en descuento - Se aplicó 10% cuando corresponde 15%',
                'categoria' => 'ERROR_CALCULO',
                'requiere_justificacion' => false,
                'orden' => 12
            ],

            // CATEGORÍA: ERROR_TRIBUTARIO
            [
                'codigo' => 'ERR_AFECTACION',
                'nombre' => 'Error en Tipo de Afectación Tributaria',
                'descripcion' => 'Se aplicó un tipo de afectación tributaria incorrecta (gravado, exonerado, inafecto)',
                'ejemplo' => 'Error en afectación - Se emitió como gravado cuando debía ser exonerado',
                'categoria' => 'ERROR_TRIBUTARIO',
                'requiere_justificacion' => true,
                'orden' => 13
            ],
            [
                'codigo' => 'ERR_TASA_IGV',
                'nombre' => 'Error en Tasa de IGV',
                'descripcion' => 'Se aplicó una tasa de IGV incorrecta',
                'ejemplo' => 'Error en tasa de IGV - Se aplicó 18% cuando corresponde tasa diferenciada',
                'categoria' => 'ERROR_TRIBUTARIO',
                'requiere_justificacion' => true,
                'orden' => 14
            ],
            [
                'codigo' => 'ERR_ICBPER',
                'nombre' => 'Error en Aplicación de ICBPER',
                'descripcion' => 'Error en el cálculo o aplicación del Impuesto a las Bolsas Plásticas',
                'ejemplo' => 'Error en ICBPER - Se aplicó impuesto cuando no corresponde',
                'categoria' => 'ERROR_TRIBUTARIO',
                'requiere_justificacion' => false,
                'orden' => 15
            ],

            // CATEGORÍA: ERROR_ADMINISTRATIVO
            [
                'codigo' => 'DUPLICADO',
                'nombre' => 'Comprobante Duplicado',
                'descripcion' => 'Se emitió el mismo comprobante dos veces o existe duplicidad',
                'ejemplo' => 'Comprobante duplicado - Ya existe F001-00123 para la misma operación',
                'categoria' => 'ERROR_ADMINISTRATIVO',
                'requiere_justificacion' => true,
                'orden' => 16
            ],
            [
                'codigo' => 'EMISION_ERROR',
                'nombre' => 'Comprobante Emitido por Error',
                'descripcion' => 'El comprobante se emitió por error del sistema o del operador',
                'ejemplo' => 'Emisión por error - Sistema generó comprobante de prueba sin autorización',
                'categoria' => 'ERROR_ADMINISTRATIVO',
                'requiere_justificacion' => true,
                'orden' => 17
            ],
            [
                'codigo' => 'TIPO_DOC_ERROR',
                'nombre' => 'Error en Tipo de Comprobante',
                'descripcion' => 'Se emitió un tipo de comprobante incorrecto',
                'ejemplo' => 'Error en tipo de comprobante - Se emitió factura cuando corresponde boleta',
                'categoria' => 'ERROR_ADMINISTRATIVO',
                'requiere_justificacion' => true,
                'orden' => 18
            ],
            [
                'codigo' => 'CLIENTE_ERROR',
                'nombre' => 'Emitido a Cliente Equivocado',
                'descripcion' => 'El comprobante se emitió a un cliente diferente al que corresponde',
                'ejemplo' => 'Cliente equivocado - Se emitió a cliente A cuando corresponde a cliente B',
                'categoria' => 'ERROR_ADMINISTRATIVO',
                'requiere_justificacion' => true,
                'orden' => 19
            ],
            [
                'codigo' => 'FECHA_ERROR',
                'nombre' => 'Error en Fecha de Emisión',
                'descripcion' => 'La fecha de emisión del comprobante es incorrecta',
                'ejemplo' => 'Error en fecha - Se consignó 15/11/2025 cuando corresponde 19/11/2025',
                'categoria' => 'ERROR_ADMINISTRATIVO',
                'requiere_justificacion' => false,
                'orden' => 20
            ],

            // CATEGORÍA: OPERACION_NO_REALIZADA
            [
                'codigo' => 'VENTA_CANCELADA',
                'nombre' => 'Venta Cancelada por Cliente',
                'descripcion' => 'El cliente canceló la compra antes de recibir los bienes o servicios',
                'ejemplo' => 'Venta cancelada - Cliente canceló pedido antes de entrega de mercadería',
                'categoria' => 'OPERACION_NO_REALIZADA',
                'requiere_justificacion' => true,
                'orden' => 21
            ],
            [
                'codigo' => 'SERVICIO_NO_PRESTADO',
                'nombre' => 'Servicio No Prestado',
                'descripcion' => 'El servicio facturado no llegó a prestarse',
                'ejemplo' => 'Servicio no prestado - Consultoría cancelada por caso fortuito',
                'categoria' => 'OPERACION_NO_REALIZADA',
                'requiere_justificacion' => true,
                'orden' => 22
            ],
            [
                'codigo' => 'MERCADERIA_DEVUELTA',
                'nombre' => 'Mercadería Devuelta (antes de entrega)',
                'descripcion' => 'La mercadería fue devuelta antes de ser entregada al cliente',
                'ejemplo' => 'Mercadería devuelta - Productos rechazados antes de entrega al cliente',
                'categoria' => 'OPERACION_NO_REALIZADA',
                'requiere_justificacion' => true,
                'orden' => 23
            ],
            [
                'codigo' => 'OP_NO_CONCRETADA',
                'nombre' => 'Operación No Concretada',
                'descripcion' => 'La operación comercial no se concretó',
                'ejemplo' => 'Operación no realizada - No se concretó la venta por falta de stock',
                'categoria' => 'OPERACION_NO_REALIZADA',
                'requiere_justificacion' => true,
                'orden' => 24
            ],

            // CATEGORÍA: ERROR_DOCUMENTO
            [
                'codigo' => 'DOC_NO_ENTREGADO',
                'nombre' => 'Documento No Entregado al Cliente',
                'descripcion' => 'El comprobante nunca fue entregado al cliente',
                'ejemplo' => 'Documento no entregado - Comprobante no llegó al cliente por error en envío',
                'categoria' => 'ERROR_DOCUMENTO',
                'requiere_justificacion' => true,
                'orden' => 25
            ],
            [
                'codigo' => 'DOC_PERDIDO',
                'nombre' => 'Pérdida del Comprobante Físico',
                'descripcion' => 'El documento físico se perdió antes de ser entregado',
                'ejemplo' => 'Documento perdido - Comprobante físico extraviado antes de entrega',
                'categoria' => 'ERROR_DOCUMENTO',
                'requiere_justificacion' => true,
                'orden' => 26
            ],
            [
                'codigo' => 'DOC_DETERIORADO',
                'nombre' => 'Comprobante Físico Deteriorado',
                'descripcion' => 'El documento físico se deterioró antes de ser entregado',
                'ejemplo' => 'Documento deteriorado - Comprobante dañado antes de entrega al cliente',
                'categoria' => 'ERROR_DOCUMENTO',
                'requiere_justificacion' => true,
                'orden' => 27
            ],

            // CATEGORÍA: ERROR_PAGO
            [
                'codigo' => 'FORMA_PAGO_ERROR',
                'nombre' => 'Error en Forma de Pago',
                'descripcion' => 'Se registró incorrectamente la forma de pago (contado/crédito)',
                'ejemplo' => 'Error en forma de pago - Se indicó contado cuando es crédito a 30 días',
                'categoria' => 'ERROR_PAGO',
                'requiere_justificacion' => false,
                'orden' => 28
            ],
            [
                'codigo' => 'MEDIO_PAGO_ERROR',
                'nombre' => 'Error en Medio de Pago',
                'descripcion' => 'Se indicó un medio de pago incorrecto',
                'ejemplo' => 'Error en medio de pago - Se indicó efectivo cuando fue transferencia bancaria',
                'categoria' => 'ERROR_PAGO',
                'requiere_justificacion' => false,
                'orden' => 29
            ],
            [
                'codigo' => 'PLAZO_PAGO_ERROR',
                'nombre' => 'Error en Plazos de Pago',
                'descripcion' => 'Los plazos de pago registrados son incorrectos',
                'ejemplo' => 'Error en plazos - Se indicó 30 días cuando corresponde 45 días',
                'categoria' => 'ERROR_PAGO',
                'requiere_justificacion' => false,
                'orden' => 30
            ],

            // CATEGORÍA: OTROS
            [
                'codigo' => 'OTROS',
                'nombre' => 'Otros Motivos',
                'descripcion' => 'Otros motivos no contemplados en las categorías anteriores',
                'ejemplo' => 'Especificar detalladamente el motivo de la anulación',
                'categoria' => 'OTROS',
                'requiere_justificacion' => true,
                'orden' => 99
            ],
        ];

        foreach ($reasons as $reason) {
            VoidedReason::updateOrCreate(
                ['codigo' => $reason['codigo']],
                $reason
            );
        }
    }
}
