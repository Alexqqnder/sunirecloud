<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DetraccionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    protected DetraccionService $detraccionService;

    public function __construct(DetraccionService $detraccionService)
    {
        $this->detraccionService = $detraccionService;
    }

    /**
     * Obtener catálogo completo de detracciones (Catálogo No. 54 SUNAT)
     *
     * @return JsonResponse
     */
    public function getDetracciones(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Catálogo No. 54 - Códigos de bienes y servicios sujetos a detracción',
            'data' => $this->detraccionService->getCatalogo(),
            'meta' => [
                'total' => count($this->detraccionService->getCatalogo()),
                'fuente' => 'SUNAT - Catálogo No. 54',
                'uso' => 'Enviar solo el codigo_bien_servicio en el campo detraccion.codigo_bien_servicio. El porcentaje y monto se calculan automáticamente.'
            ]
        ]);
    }

    /**
     * Obtener información de una detracción específica por código
     *
     * @param string $codigo
     * @return JsonResponse
     */
    public function getDetraccionPorCodigo(string $codigo): JsonResponse
    {
        $detraccion = $this->detraccionService->getDetraccionPorCodigo($codigo);

        if (!$detraccion) {
            return response()->json([
                'success' => false,
                'message' => "Código de detracción '{$codigo}' no encontrado",
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detraccion
        ]);
    }

    /**
     * Buscar detracciones por descripción
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function buscarDetracciones(Request $request): JsonResponse
    {
        $busqueda = $request->query('q', '');

        if (strlen($busqueda) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'La búsqueda debe tener al menos 2 caracteres',
                'data' => []
            ], 400);
        }

        $resultados = $this->detraccionService->buscarPorDescripcion($busqueda);

        return response()->json([
            'success' => true,
            'data' => $resultados,
            'meta' => [
                'busqueda' => $busqueda,
                'resultados' => count($resultados)
            ]
        ]);
    }

    /**
     * Obtener detracciones agrupadas por porcentaje
     *
     * @return JsonResponse
     */
    public function getDetraccionesPorPorcentaje(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->detraccionService->getDetraccionesPorPorcentaje()
        ]);
    }

    /**
     * Obtener medios de pago para detracción
     *
     * @return JsonResponse
     */
    public function getMediosPagoDetraccion(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Medios de pago para detracción',
            'data' => $this->detraccionService->getMediosPago(),
            'meta' => [
                'default' => '001',
                'default_descripcion' => 'Depósito en cuenta'
            ]
        ]);
    }

    /**
     * Calcular detracción (simulación)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calcularDetraccion(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_bien_servicio' => 'required|string|max:3',
            'monto_total' => 'required|numeric|min:0'
        ]);

        try {
            $resultado = $this->detraccionService->calcularDetraccion(
                $request->input('monto_total'),
                $request->input('codigo_bien_servicio'),
                $request->input('porcentaje_personalizado')
            );

            $infoCatalogo = $this->detraccionService->getDetraccionPorCodigo($request->input('codigo_bien_servicio'));

            return response()->json([
                'success' => true,
                'message' => 'Detracción calculada exitosamente',
                'data' => [
                    'codigo_bien_servicio' => $resultado['codigo'],
                    'descripcion' => $infoCatalogo['descripcion'] ?? null,
                    'monto_total_operacion' => $request->input('monto_total'),
                    'porcentaje_detraccion' => $resultado['porcentaje'],
                    'monto_detraccion' => $resultado['monto'],
                    'monto_neto_a_pagar' => round($request->input('monto_total') - $resultado['monto'], 2)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
