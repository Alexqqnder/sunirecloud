<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class DetraccionService
{
    /**
     * Catálogo No. 54: Códigos de bienes y servicios sujetos a detracción
     * Fuente: SUNAT - Actualizado según normativa vigente
     *
     * Estructura: código_sunat => [descripción, porcentaje, código_factpro]
     */
    public const CATALOGO_DETRACCIONES = [
        '001' => [
            'descripcion' => 'Azúcar y melaza de caña',
            'porcentaje' => 10.00,
            'codigo_factpro' => '8'
        ],
        '003' => [
            'descripcion' => 'Alcohol etílico',
            'porcentaje' => 10.00,
            'codigo_factpro' => '9'
        ],
        '004' => [
            'descripcion' => 'Recursos hidrobiológicos',
            'porcentaje' => 4.00,
            'codigo_factpro' => '10'
        ],
        '005' => [
            'descripcion' => 'Maíz amarillo duro',
            'porcentaje' => 4.00,
            'codigo_factpro' => '11'
        ],
        '007' => [
            'descripcion' => 'Caña de azúcar',
            'porcentaje' => 10.00,
            'codigo_factpro' => '12'
        ],
        '008' => [
            'descripcion' => 'Madera',
            'porcentaje' => 4.00,
            'codigo_factpro' => '13'
        ],
        '009' => [
            'descripcion' => 'Arena y piedra',
            'porcentaje' => 10.00,
            'codigo_factpro' => '14'
        ],
        '010' => [
            'descripcion' => 'Residuos, subproductos, desechos, recortes y desperdicios',
            'porcentaje' => 15.00,
            'codigo_factpro' => '15'
        ],
        '012' => [
            'descripcion' => 'Intermediación laboral y tercerización',
            'porcentaje' => 12.00,
            'codigo_factpro' => '16'
        ],
        '014' => [
            'descripcion' => 'Carnes y despojos comestibles',
            'porcentaje' => 4.00,
            'codigo_factpro' => '17'
        ],
        '016' => [
            'descripcion' => 'Aceite de pescado',
            'porcentaje' => 10.00,
            'codigo_factpro' => '18'
        ],
        '017' => [
            'descripcion' => 'Harina, polvo y pellets de pescado, crustáceos, moluscos y demás invertebrados acuáticos',
            'porcentaje' => 4.00,
            'codigo_factpro' => '19'
        ],
        '019' => [
            'descripcion' => 'Arrendamiento de bienes muebles',
            'porcentaje' => 10.00,
            'codigo_factpro' => '20'
        ],
        '020' => [
            'descripcion' => 'Mantenimiento y reparación de bienes muebles',
            'porcentaje' => 12.00,
            'codigo_factpro' => '21'
        ],
        '021' => [
            'descripcion' => 'Movimiento de carga',
            'porcentaje' => 10.00,
            'codigo_factpro' => '22'
        ],
        '022' => [
            'descripcion' => 'Otros servicios empresariales',
            'porcentaje' => 12.00,
            'codigo_factpro' => '23'
        ],
        '023' => [
            'descripcion' => 'Leche',
            'porcentaje' => 4.00,
            'codigo_factpro' => '24'
        ],
        '024' => [
            'descripcion' => 'Comisión mercantil',
            'porcentaje' => 10.00,
            'codigo_factpro' => '25'
        ],
        '025' => [
            'descripcion' => 'Fabricación de bienes por encargo',
            'porcentaje' => 10.00,
            'codigo_factpro' => '26'
        ],
        '026' => [
            'descripcion' => 'Servicio de transporte de personas',
            'porcentaje' => 10.00,
            'codigo_factpro' => '27'
        ],
        '027' => [
            'descripcion' => 'Servicio de transporte de carga',
            'porcentaje' => 4.00,
            'codigo_factpro' => '28'
        ],
        '028' => [
            'descripcion' => 'Transporte de pasajeros',
            'porcentaje' => 10.00,
            'codigo_factpro' => '29'
        ],
        '030' => [
            'descripcion' => 'Contratos de construcción',
            'porcentaje' => 4.00,
            'codigo_factpro' => '30'
        ],
        '031' => [
            'descripcion' => 'Oro gravado con el IGV',
            'porcentaje' => 10.00,
            'codigo_factpro' => '31'
        ],
        '032' => [
            'descripcion' => 'Páprika y otros frutos de los géneros capsicum o pimienta',
            'porcentaje' => 10.00,
            'codigo_factpro' => '32'
        ],
        '034' => [
            'descripcion' => 'Minerales metálicos no auríferos',
            'porcentaje' => 10.00,
            'codigo_factpro' => '33'
        ],
        '035' => [
            'descripcion' => 'Bienes exonerados del IGV',
            'porcentaje' => 1.50,
            'codigo_factpro' => '34'
        ],
        '036' => [
            'descripcion' => 'Oro y demás minerales metálicos exonerados del IGV',
            'porcentaje' => 1.50,
            'codigo_factpro' => '35'
        ],
        '037' => [
            'descripcion' => 'Demás servicios gravados con el IGV',
            'porcentaje' => 12.00,
            'codigo_factpro' => '36'
        ],
        '039' => [
            'descripcion' => 'Minerales no metálicos',
            'porcentaje' => 10.00,
            'codigo_factpro' => '37'
        ],
        '040' => [
            'descripcion' => 'Bien inmueble gravado con IGV',
            'porcentaje' => 4.00,
            'codigo_factpro' => '38'
        ],
        '041' => [
            'descripcion' => 'Plomo',
            'porcentaje' => 15.00,
            'codigo_factpro' => '39'
        ],
        '099' => [
            'descripcion' => 'Ley 30737',
            'porcentaje' => 0.00, // Porcentaje variable según ley
            'codigo_factpro' => '40'
        ],
    ];

    /**
     * Catálogo de medios de pago para detracción
     */
    public const MEDIOS_PAGO = [
        '001' => 'Depósito en cuenta',
        '002' => 'Giro',
        '003' => 'Transferencia de fondos',
        '004' => 'Orden de pago',
        '005' => 'Tarjeta de débito',
        '006' => 'Tarjeta de crédito emitida en el país por una empresa del sistema financiero',
        '007' => 'Cheques con la cláusula de "NO NEGOCIABLE"',
        '008' => 'Efectivo',
        '009' => 'Efectivo, por operaciones en las que no existe obligación de utilizar medio de pago',
        '010' => 'Medios de pago usados en comercio exterior',
        '011' => 'Documentos emitidos por las EDPYMES',
        '012' => 'Tarjeta de crédito emitida en el exterior',
        '101' => 'Transferencias – Comercio exterior',
        '102' => 'Cheques bancarios – Comercio exterior',
        '103' => 'Orden de pago simple – Comercio exterior',
        '104' => 'Orden de pago documentario – Comercio exterior',
        '105' => 'Remesa simple – Comercio exterior',
        '106' => 'Remesa documentaria – Comercio exterior',
        '107' => 'Carta de crédito simple – Comercio exterior',
        '108' => 'Carta de crédito documentario – Comercio exterior',
    ];

    /**
     * Cuenta por defecto del Banco de la Nación para detracciones
     * Esta cuenta puede ser configurada por empresa
     */
    public const CUENTA_BANCO_NACION_DEFAULT = '';

    /**
     * Código de medio de pago por defecto (Depósito en cuenta)
     */
    public const MEDIO_PAGO_DEFAULT = '001';

    /**
     * Obtener el catálogo completo de detracciones
     *
     * @return array
     */
    public function getCatalogo(): array
    {
        $catalogo = [];

        foreach (self::CATALOGO_DETRACCIONES as $codigo => $datos) {
            $catalogo[] = [
                'codigo' => $codigo,
                'descripcion' => $datos['descripcion'],
                'porcentaje' => $datos['porcentaje'],
                'codigo_factpro' => $datos['codigo_factpro']
            ];
        }

        return $catalogo;
    }

    /**
     * Obtener los medios de pago disponibles
     *
     * @return array
     */
    public function getMediosPago(): array
    {
        $medios = [];

        foreach (self::MEDIOS_PAGO as $codigo => $descripcion) {
            $medios[] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion
            ];
        }

        return $medios;
    }

    /**
     * Obtener información de una detracción por código
     *
     * @param string $codigo Código del bien/servicio
     * @return array|null
     */
    public function getDetraccionPorCodigo(string $codigo): ?array
    {
        $codigo = str_pad($codigo, 3, '0', STR_PAD_LEFT);

        if (!isset(self::CATALOGO_DETRACCIONES[$codigo])) {
            return null;
        }

        return [
            'codigo' => $codigo,
            'descripcion' => self::CATALOGO_DETRACCIONES[$codigo]['descripcion'],
            'porcentaje' => self::CATALOGO_DETRACCIONES[$codigo]['porcentaje'],
            'codigo_factpro' => self::CATALOGO_DETRACCIONES[$codigo]['codigo_factpro']
        ];
    }

    /**
     * Obtener el porcentaje de detracción por código
     *
     * @param string $codigo
     * @return float|null
     */
    public function getPorcentaje(string $codigo): ?float
    {
        $codigo = str_pad($codigo, 3, '0', STR_PAD_LEFT);

        return self::CATALOGO_DETRACCIONES[$codigo]['porcentaje'] ?? null;
    }

    /**
     * Calcular el monto de detracción
     *
     * @param float $montoTotal Monto total de la operación (incluye IGV)
     * @param string $codigoBienServicio Código del bien/servicio sujeto a detracción
     * @param float|null $porcentajePersonalizado Porcentaje personalizado (opcional)
     * @return array{monto: float, porcentaje: float, codigo: string}
     * @throws Exception
     */
    public function calcularDetraccion(
        float $montoTotal,
        string $codigoBienServicio,
        ?float $porcentajePersonalizado = null
    ): array {
        $codigo = str_pad($codigoBienServicio, 3, '0', STR_PAD_LEFT);

        // Obtener porcentaje del catálogo o usar el personalizado
        $porcentaje = $porcentajePersonalizado ?? $this->getPorcentaje($codigo);

        if ($porcentaje === null) {
            throw new Exception("Código de bien/servicio no válido: {$codigo}");
        }

        // Calcular monto de detracción
        $monto = round($montoTotal * ($porcentaje / 100), 2);

        Log::info('Detracción calculada', [
            'monto_total' => $montoTotal,
            'codigo' => $codigo,
            'porcentaje' => $porcentaje,
            'monto_detraccion' => $monto
        ]);

        return [
            'codigo' => $codigo,
            'porcentaje' => $porcentaje,
            'monto' => $monto
        ];
    }

    /**
     * Procesar y completar datos de detracción
     * Recibe datos mínimos del usuario y devuelve datos completos
     *
     * @param array $detraccionInput Datos de entrada del usuario
     * @param float $montoTotalOperacion Monto total de la operación
     * @param string|null $cuentaBancoEmpresa Cuenta del Banco de la Nación de la empresa
     * @return array Datos completos de detracción
     * @throws Exception
     */
    public function procesarDetraccion(
        array $detraccionInput,
        float $montoTotalOperacion,
        ?string $cuentaBancoEmpresa = null
    ): array {
        // Validar código de bien/servicio (obligatorio)
        if (empty($detraccionInput['codigo_bien_servicio'])) {
            throw new Exception('El código de bien/servicio es obligatorio para la detracción');
        }

        $codigo = str_pad($detraccionInput['codigo_bien_servicio'], 3, '0', STR_PAD_LEFT);

        // Validar que el código existe en el catálogo
        if (!isset(self::CATALOGO_DETRACCIONES[$codigo])) {
            throw new Exception("Código de bien/servicio no válido: {$codigo}. Consulte el catálogo de detracciones.");
        }

        // Obtener información del catálogo
        $infoCatalogo = self::CATALOGO_DETRACCIONES[$codigo];

        // Determinar porcentaje (del catálogo o personalizado)
        $porcentaje = $detraccionInput['porcentaje'] ?? $infoCatalogo['porcentaje'];

        // Calcular monto automáticamente si no se proporcionó
        $monto = $detraccionInput['monto'] ?? round($montoTotalOperacion * ($porcentaje / 100), 2);

        // Determinar cuenta del banco (prioridad: input > empresa > default)
        $cuentaBanco = $detraccionInput['cuenta_banco']
            ?? $cuentaBancoEmpresa
            ?? self::CUENTA_BANCO_NACION_DEFAULT;

        // Determinar medio de pago (default: depósito en cuenta)
        $codigoMedioPago = $detraccionInput['codigo_medio_pago'] ?? self::MEDIO_PAGO_DEFAULT;

        // Validar medio de pago
        if (!isset(self::MEDIOS_PAGO[$codigoMedioPago])) {
            $codigoMedioPago = self::MEDIO_PAGO_DEFAULT;
        }

        $resultado = [
            'codigo_bien_servicio' => $codigo,
            'descripcion_bien_servicio' => $infoCatalogo['descripcion'],
            'codigo_medio_pago' => $codigoMedioPago,
            'descripcion_medio_pago' => self::MEDIOS_PAGO[$codigoMedioPago],
            'cuenta_banco' => $cuentaBanco,
            'porcentaje' => $porcentaje,
            'monto' => $monto
        ];

        Log::info('Detracción procesada', [
            'input' => $detraccionInput,
            'monto_total_operacion' => $montoTotalOperacion,
            'resultado' => $resultado
        ]);

        return $resultado;
    }

    /**
     * Validar si un código de bien/servicio es válido
     *
     * @param string $codigo
     * @return bool
     */
    public function esCodigoValido(string $codigo): bool
    {
        $codigo = str_pad($codigo, 3, '0', STR_PAD_LEFT);
        return isset(self::CATALOGO_DETRACCIONES[$codigo]);
    }

    /**
     * Buscar códigos de detracción por descripción
     *
     * @param string $busqueda
     * @return array
     */
    public function buscarPorDescripcion(string $busqueda): array
    {
        $resultados = [];
        $busqueda = strtolower($busqueda);

        foreach (self::CATALOGO_DETRACCIONES as $codigo => $datos) {
            if (str_contains(strtolower($datos['descripcion']), $busqueda)) {
                $resultados[] = [
                    'codigo' => $codigo,
                    'descripcion' => $datos['descripcion'],
                    'porcentaje' => $datos['porcentaje'],
                    'codigo_factpro' => $datos['codigo_factpro']
                ];
            }
        }

        return $resultados;
    }

    /**
     * Obtener detracciones agrupadas por porcentaje
     *
     * @return array
     */
    public function getDetraccionesPorPorcentaje(): array
    {
        $agrupado = [];

        foreach (self::CATALOGO_DETRACCIONES as $codigo => $datos) {
            $porcentaje = $datos['porcentaje'];
            $key = number_format($porcentaje, 2) . '%';

            if (!isset($agrupado[$key])) {
                $agrupado[$key] = [];
            }

            $agrupado[$key][] = [
                'codigo' => $codigo,
                'descripcion' => $datos['descripcion'],
                'porcentaje' => $porcentaje
            ];
        }

        ksort($agrupado);

        return $agrupado;
    }
}
