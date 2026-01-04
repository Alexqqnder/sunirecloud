<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BancarizacionService;
use App\Models\Invoice;
use App\Models\Boleta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BancarizacionController extends Controller
{
    protected BancarizacionService $bancarizacionService;

    public function __construct(BancarizacionService $bancarizacionService)
    {
        $this->bancarizacionService = $bancarizacionService;
    }

    /**
     * Obtener catálogo de medios de pago válidos
     *
     * GET /api/v1/bancarizacion/medios-pago
     */
    public function getMediosPago(): JsonResponse
    {
        try {
            $mediosPago = $this->bancarizacionService->getMediosPagoActivos();

            return response()->json([
                'success' => true,
                'data' => $mediosPago,
                'total' => $mediosPago->count(),
                'message' => 'Medios de pago obtenidos correctamente',
                'info' => [
                    'ley' => 'Ley N° 28194 - Bancarización',
                    'umbral_pen' => 'S/ 2,000.00',
                    'umbral_usd' => 'US$ 500.00'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener medios de pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar si una operación aplica bancarización
     *
     * POST /api/v1/bancarizacion/validar
     * {
     *   "monto_total": 2500.00,
     *   "moneda": "PEN",
     *   "bancarizacion": { ... }  // opcional
     * }
     */
    public function validar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'monto_total' => 'required|numeric|min:0',
                'moneda' => 'required|string|in:PEN,USD',
                'bancarizacion' => 'nullable|array'
            ]);

            $montoTotal = $request->input('monto_total');
            $moneda = $request->input('moneda');
            $dataBancarizacion = $request->input('bancarizacion');

            // Verificar si aplica bancarización
            $aplica = $this->bancarizacionService->aplicaBancarizacion($montoTotal, $moneda);
            $umbral = $this->bancarizacionService->getUmbral($moneda);

            // Verificar advertencias
            $advertencias = $this->bancarizacionService->verificarAdvertencias($montoTotal, $moneda, $dataBancarizacion);

            $response = [
                'success' => true,
                'aplica_bancarizacion' => $aplica,
                'monto_total' => $montoTotal,
                'moneda' => $moneda,
                'umbral' => $umbral,
                'diferencia' => $aplica ? ($montoTotal - $umbral) : null,
                'ley' => 'Ley N° 28194',
                'tiene_advertencia' => $advertencias['tiene_advertencia'],
                'advertencia' => $advertencias['mensaje']
            ];

            // Si se proporcionaron datos de bancarización, validarlos
            if ($dataBancarizacion) {
                $validacion = $this->bancarizacionService->validarDatosBancarizacion($dataBancarizacion);
                $response['validacion_datos'] = [
                    'valido' => $validacion['valido'],
                    'errores' => $validacion['errores'] ?? [],
                    'medio_pago' => $validacion['medio_pago'] ?? null
                ];
            }

            return response()->json($response);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar bancarización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de documentos sin bancarización que deberían tenerla
     *
     * GET /api/v1/bancarizacion/reportes/sin-bancarizacion
     * ?tipo_documento=factura|boleta|todos
     * &fecha_desde=2025-01-01
     * &fecha_hasta=2025-12-31
     */
    public function reporteSinBancarizacion(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tipo_documento' => 'nullable|string|in:factura,boleta,todos',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde'
            ]);

            $tipoDocumento = $request->input('tipo_documento', 'todos');
            $fechaDesde = $request->input('fecha_desde');
            $fechaHasta = $request->input('fecha_hasta');

            $resultados = [];

            // Buscar facturas sin bancarización
            if (in_array($tipoDocumento, ['factura', 'todos'])) {
                $queryInvoices = Invoice::where('bancarizacion_aplica', true)
                    ->whereNull('bancarizacion_medio_pago');

                if ($fechaDesde) {
                    $queryInvoices->whereDate('fecha_emision', '>=', $fechaDesde);
                }
                if ($fechaHasta) {
                    $queryInvoices->whereDate('fecha_emision', '<=', $fechaHasta);
                }

                $facturas = $queryInvoices->with(['company', 'branch', 'client'])
                    ->orderBy('fecha_emision', 'desc')
                    ->get()
                    ->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'tipo' => 'FACTURA',
                            'numero' => $invoice->numero_completo,
                            'fecha_emision' => $invoice->fecha_emision->format('Y-m-d'),
                            'moneda' => $invoice->moneda,
                            'monto_total' => $invoice->mto_imp_venta,
                            'umbral' => $invoice->bancarizacion_monto_umbral,
                            'diferencia' => $invoice->mto_imp_venta - $invoice->bancarizacion_monto_umbral,
                            'cliente' => $invoice->client->razon_social ?? 'Sin cliente',
                            'estado_sunat' => $invoice->estado_sunat
                        ];
                    });

                $resultados['facturas'] = $facturas;
            }

            // Buscar boletas sin bancarización
            if (in_array($tipoDocumento, ['boleta', 'todos'])) {
                $queryBoletas = Boleta::where('bancarizacion_aplica', true)
                    ->whereNull('bancarizacion_medio_pago');

                if ($fechaDesde) {
                    $queryBoletas->whereDate('fecha_emision', '>=', $fechaDesde);
                }
                if ($fechaHasta) {
                    $queryBoletas->whereDate('fecha_emision', '<=', $fechaHasta);
                }

                $boletas = $queryBoletas->with(['company', 'branch', 'client'])
                    ->orderBy('fecha_emision', 'desc')
                    ->get()
                    ->map(function ($boleta) {
                        return [
                            'id' => $boleta->id,
                            'tipo' => 'BOLETA',
                            'numero' => $boleta->numero_completo,
                            'fecha_emision' => $boleta->fecha_emision->format('Y-m-d'),
                            'moneda' => $boleta->moneda,
                            'monto_total' => $boleta->mto_imp_venta,
                            'umbral' => $boleta->bancarizacion_monto_umbral,
                            'diferencia' => $boleta->mto_imp_venta - $boleta->bancarizacion_monto_umbral,
                            'cliente' => $boleta->client->razon_social ?? 'Sin cliente',
                            'estado_sunat' => $boleta->estado_sunat
                        ];
                    });

                $resultados['boletas'] = $boletas;
            }

            // Calcular totales
            $totalFacturas = isset($resultados['facturas']) ? $resultados['facturas']->count() : 0;
            $totalBoletas = isset($resultados['boletas']) ? $resultados['boletas']->count() : 0;
            $totalGeneral = $totalFacturas + $totalBoletas;

            return response()->json([
                'success' => true,
                'data' => $resultados,
                'resumen' => [
                    'total_facturas' => $totalFacturas,
                    'total_boletas' => $totalBoletas,
                    'total_general' => $totalGeneral
                ],
                'filtros' => [
                    'tipo_documento' => $tipoDocumento,
                    'fecha_desde' => $fechaDesde,
                    'fecha_hasta' => $fechaHasta
                ],
                'advertencia' => $totalGeneral > 0
                    ? "⚠️ Se encontraron {$totalGeneral} documentos sujetos a bancarización sin medio de pago registrado. Estos gastos NO serán deducibles según Ley N° 28194."
                    : "✅ Todos los documentos sujetos a bancarización tienen medio de pago registrado.",
                'message' => 'Reporte generado correctamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros de búsqueda incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de bancarización
     *
     * GET /api/v1/bancarizacion/estadisticas
     * ?fecha_desde=2025-01-01
     * &fecha_hasta=2025-12-31
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde'
            ]);

            $fechaDesde = $request->input('fecha_desde');
            $fechaHasta = $request->input('fecha_hasta');

            // Estadísticas de facturas
            $queryInvoices = Invoice::query();
            if ($fechaDesde) $queryInvoices->whereDate('fecha_emision', '>=', $fechaDesde);
            if ($fechaHasta) $queryInvoices->whereDate('fecha_emision', '<=', $fechaHasta);

            $totalFacturas = $queryInvoices->count();
            $facturasConBancarizacion = (clone $queryInvoices)->where('bancarizacion_aplica', true)->count();
            $facturasConMedioPago = (clone $queryInvoices)->where('bancarizacion_aplica', true)->whereNotNull('bancarizacion_medio_pago')->count();
            $facturasSinMedioPago = $facturasConBancarizacion - $facturasConMedioPago;

            // Estadísticas de boletas
            $queryBoletas = Boleta::query();
            if ($fechaDesde) $queryBoletas->whereDate('fecha_emision', '>=', $fechaDesde);
            if ($fechaHasta) $queryBoletas->whereDate('fecha_emision', '<=', $fechaHasta);

            $totalBoletas = $queryBoletas->count();
            $boletasConBancarizacion = (clone $queryBoletas)->where('bancarizacion_aplica', true)->count();
            $boletasConMedioPago = (clone $queryBoletas)->where('bancarizacion_aplica', true)->whereNotNull('bancarizacion_medio_pago')->count();
            $boletasSinMedioPago = $boletasConBancarizacion - $boletasConMedioPago;

            // Calcular porcentajes
            $porcentajeCumplimientoFacturas = $facturasConBancarizacion > 0
                ? round(($facturasConMedioPago / $facturasConBancarizacion) * 100, 2)
                : 100;

            $porcentajeCumplimientoBoletas = $boletasConBancarizacion > 0
                ? round(($boletasConMedioPago / $boletasConBancarizacion) * 100, 2)
                : 100;

            return response()->json([
                'success' => true,
                'data' => [
                    'facturas' => [
                        'total' => $totalFacturas,
                        'con_bancarizacion_requerida' => $facturasConBancarizacion,
                        'con_medio_pago' => $facturasConMedioPago,
                        'sin_medio_pago' => $facturasSinMedioPago,
                        'porcentaje_cumplimiento' => $porcentajeCumplimientoFacturas
                    ],
                    'boletas' => [
                        'total' => $totalBoletas,
                        'con_bancarizacion_requerida' => $boletasConBancarizacion,
                        'con_medio_pago' => $boletasConMedioPago,
                        'sin_medio_pago' => $boletasSinMedioPago,
                        'porcentaje_cumplimiento' => $porcentajeCumplimientoBoletas
                    ],
                    'general' => [
                        'total_documentos' => $totalFacturas + $totalBoletas,
                        'total_con_bancarizacion' => $facturasConBancarizacion + $boletasConBancarizacion,
                        'total_con_medio_pago' => $facturasConMedioPago + $boletasConMedioPago,
                        'total_sin_medio_pago' => $facturasSinMedioPago + $boletasSinMedioPago,
                        'porcentaje_cumplimiento_general' => ($facturasConBancarizacion + $boletasConBancarizacion) > 0
                            ? round((($facturasConMedioPago + $boletasConMedioPago) / ($facturasConBancarizacion + $boletasConBancarizacion)) * 100, 2)
                            : 100
                    ]
                ],
                'filtros' => [
                    'fecha_desde' => $fechaDesde,
                    'fecha_hasta' => $fechaHasta
                ],
                'message' => 'Estadísticas generadas correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
