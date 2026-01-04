<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesPdfGeneration;
use App\Http\Requests\Boleta\CreateDailySummaryRequest;
use App\Http\Requests\Boleta\GetBoletasPendingRequest;
use App\Http\Requests\Boleta\IndexBoletaRequest;
use App\Http\Requests\Boleta\StoreBoletaRequest;
use App\Http\Requests\Boleta\UpdateBoletaRequest;
use App\Models\Boleta;
use App\Models\DailySummary;
use App\Services\DocumentService;
use App\Services\FileService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BoletaController extends Controller
{
    use HandlesPdfGeneration;

    protected DocumentService $documentService;
    protected FileService $fileService;

    public function __construct(DocumentService $documentService, FileService $fileService)
    {
        $this->documentService = $documentService;
        $this->fileService = $fileService;
    }

    /**
     * Listar boletas con filtros
     */
    public function index(IndexBoletaRequest $request): JsonResponse
    {
        try {
            $query = Boleta::with(['company', 'branch', 'client']);
            $this->applyFilters($query, $request);
            
            $perPage = $request->get('per_page', 15);
            $boletas = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $boletas->items(),
                'pagination' => $this->getPaginationData($boletas)
            ]);

        } catch (Exception $e) {
            return $this->errorResponse('Error al listar boletas', $e);
        }
    }

    /**
     * Crear nueva boleta
     */
    public function store(StoreBoletaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $boleta = $this->documentService->createBoleta($validated);

            return response()->json([
                'success' => true,
                'data' => $boleta->load(['company', 'branch', 'client']),
                'message' => 'Boleta creada correctamente'
            ], 201);

        } catch (Exception $e) {
            return $this->errorResponse('Error al crear la boleta', $e);
        }
    }

    /**
     * Obtener boleta específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            $boleta = Boleta::with(['company', 'branch', 'client'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $boleta
            ]);
        } catch (Exception $e) {
            return $this->notFoundResponse('Boleta no encontrada');
        }
    }

    /**
     * Actualizar boleta
     */
    public function update(UpdateBoletaRequest $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Actualizar la boleta
            $boleta = $this->documentService->updateBoleta($id, $validated);

            return response()->json([
                'success' => true,
                'data' => $boleta->load(['company', 'branch', 'client']),
                'message' => 'Boleta actualizada correctamente. Estado restablecido a PENDIENTE para reenvío.'
            ]);

        } catch (Exception $e) {
            return $this->errorResponse('Error al actualizar la boleta', $e);
        }
    }

    /**
     * Enviar boleta a SUNAT
     */
    public function sendToSunat(string $id): JsonResponse
    {
        try {
            $boleta = Boleta::with(['company', 'branch', 'client'])->findOrFail($id);

            // Validar que no haya sido ACEPTADO (permitir reenvío de RECHAZADOS y PENDIENTES)
            if ($boleta->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'La boleta ya fue aceptada por SUNAT'
                ], 400);
            }

            // Log del reenvío si es RECHAZADO
            if ($boleta->estado_sunat === 'RECHAZADO') {
                Log::info('Reenviando boleta rechazada a SUNAT', [
                    'boleta_id' => $boleta->id,
                    'numero' => $boleta->numero_completo,
                    'rechazo_anterior' => $boleta->respuesta_sunat
                ]);
            }

            $result = $this->documentService->sendToSunat($boleta, 'boleta');
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch', 'client']),
                    'message' => 'Boleta enviada exitosamente a SUNAT'
                ]);
            }

            return $this->handleSunatError($result);

        } catch (Exception $e) {
            return $this->errorResponse('Error interno al enviar a SUNAT', $e);
        }
    }

    /**
     * Descargar XML de boleta
     */
    public function downloadXml(string $id): Response
    {
        try {
            $boleta = Boleta::findOrFail($id);
            
            if (!$this->fileService->fileExists($boleta->xml_path)) {
                return $this->notFoundResponse('XML no encontrado');
            }

            return $this->fileService->downloadFile(
                $boleta->xml_path,
                $boleta->numero_completo . '.xml',
                ['Content-Type' => 'application/xml']
            );

        } catch (Exception $e) {
            return $this->errorResponse('Error al descargar XML', $e);
        }
    }

    /**
     * Descargar CDR de boleta
     */
    public function downloadCdr(string $id): Response
    {
        try {
            $boleta = Boleta::findOrFail($id);
            
            if (!$this->fileService->fileExists($boleta->cdr_path)) {
                return $this->notFoundResponse('CDR no encontrado');
            }

            return $this->fileService->downloadFile(
                $boleta->cdr_path,
                'R-' . $boleta->numero_completo . '.zip',
                ['Content-Type' => 'application/zip']
            );

        } catch (Exception $e) {
            return $this->errorResponse('Error al descargar CDR', $e);
        }
    }

    /**
     * Descargar PDF de boleta
     */
    public function downloadPdf(string $id, Request $request): Response
    {
        try {
            $boleta = Boleta::with(['company', 'branch', 'client'])->findOrFail($id);
            return $this->downloadDocumentPdf($boleta, $request);
        } catch (Exception $e) {
            return $this->errorResponse('Error al descargar PDF', $e);
        }
    }

    /**
     * Generar PDF de boleta
     */
    public function generatePdf(string $id, Request $request): Response
    {
        try {
            $boleta = Boleta::with(['company', 'branch', 'client'])->findOrFail($id);
            return $this->generateDocumentPdf($boleta, 'boleta', $request);
        } catch (Exception $e) {
            return $this->errorResponse('Error al generar PDF', $e);
        }
    }

    /**
     * Obtener fechas con boletas pendientes de resumen diario
     * Incluye validación de plazo de 3 días calendario
     */
    public function getFechasBoletasPendientes(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:companies,id',
                'branch_id' => 'required|integer|exists:branches,id',
            ]);

            \Log::info('Consultando fechas pendientes de resumen', [
                'company_id' => $validated['company_id'],
                'branch_id' => $validated['branch_id']
            ]);

            $fechas = $this->documentService->getFechasBoletasPendientes(
                $validated['company_id'],
                $validated['branch_id']
            );

            // Si no hay fechas, retornar vacío pero exitoso
            if (empty($fechas)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'todas' => [],
                        'dentro_del_plazo' => [],
                        'urgentes' => [],
                        'vencidas' => [],
                    ],
                    'resumen' => [
                        'total_fechas' => 0,
                        'fechas_dentro_plazo' => 0,
                        'fechas_urgentes' => 0,
                        'fechas_vencidas' => 0,
                        'total_boletas' => 0,
                    ],
                    'mensaje_normativa' => 'Según RS N° 000003-2023/SUNAT, el Resumen Diario debe enviarse máximo 3 días calendario desde el día siguiente a la emisión.',
                    'message' => 'No hay boletas pendientes de resumen diario'
                ]);
            }

            // Separar por estado
            $dentroDelPlazo = array_filter($fechas, fn($f) => $f['dentro_del_plazo'] && !$f['vencido']);
            $urgentes = array_filter($fechas, fn($f) => $f['urgente'] && !$f['vencido']);
            $vencidas = array_filter($fechas, fn($f) => $f['vencido']);

            return response()->json([
                'success' => true,
                'data' => [
                    'todas' => $fechas,
                    'dentro_del_plazo' => array_values($dentroDelPlazo),
                    'urgentes' => array_values($urgentes),
                    'vencidas' => array_values($vencidas),
                ],
                'resumen' => [
                    'total_fechas' => count($fechas),
                    'fechas_dentro_plazo' => count($dentroDelPlazo),
                    'fechas_urgentes' => count($urgentes),
                    'fechas_vencidas' => count($vencidas),
                    'total_boletas' => array_sum(array_column($fechas, 'total_boletas')),
                ],
                'mensaje_normativa' => 'Según RS N° 000003-2023/SUNAT, el Resumen Diario debe enviarse máximo 3 días calendario desde el día siguiente a la emisión.',
                'message' => 'Fechas con boletas pendientes obtenidas correctamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            \Log::error('Error al obtener fechas pendientes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Error al obtener fechas pendientes', $e);
        }
    }

    /**
     * Crear resumen diario desde fecha
     */
    public function createDailySummaryFromDate(CreateDailySummaryRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $summary = $this->documentService->createSummaryFromBoletas($validated);

            return response()->json([
                'success' => true,
                'data' => $summary->load(['company', 'branch', 'boletas']),
                'message' => 'Resumen diario creado correctamente'
            ], 201);

        } catch (Exception $e) {
            return $this->errorResponse('Error al crear resumen diario', $e);
        }
    }

    /**
     * Crear resúmenes diarios para múltiples fechas
     * Ideal para crear resúmenes de varios días a la vez
     */
    public function createMultipleDailySummaries(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:companies,id',
                'branch_id' => 'required|integer|exists:branches,id',
                'fechas' => 'required|array|min:1',
                'fechas.*' => 'required|date|before_or_equal:today',
            ]);

            $resultados = [];
            $exitosos = 0;
            $fallidos = 0;

            foreach ($validated['fechas'] as $fecha) {
                try {
                    $summaryData = [
                        'company_id' => $validated['company_id'],
                        'branch_id' => $validated['branch_id'],
                        'fecha_resumen' => $fecha,
                        'ubl_version' => '2.1',
                        'moneda' => 'PEN',
                    ];

                    $summary = $this->documentService->createSummaryFromBoletas($summaryData);

                    $resultados[] = [
                        'fecha' => $fecha,
                        'success' => true,
                        'resumen_id' => $summary->id,
                        'numero_completo' => $summary->numero_completo,
                        'total_boletas' => $summary->boletas()->count(),
                        'message' => 'Resumen creado correctamente'
                    ];

                    $exitosos++;

                } catch (Exception $e) {
                    $resultados[] = [
                        'fecha' => $fecha,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'message' => 'Error al crear resumen'
                    ];

                    $fallidos++;

                    \Log::warning('Error al crear resumen diario masivo', [
                        'fecha' => $fecha,
                        'company_id' => $validated['company_id'],
                        'branch_id' => $validated['branch_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => $exitosos > 0,
                'data' => $resultados,
                'resumen' => [
                    'total_fechas' => count($validated['fechas']),
                    'exitosos' => $exitosos,
                    'fallidos' => $fallidos
                ],
                'message' => $fallidos > 0
                    ? "Se crearon {$exitosos} resúmenes correctamente, {$fallidos} fallaron"
                    : "Se crearon {$exitosos} resúmenes correctamente"
            ], $exitosos > 0 ? 201 : 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return $this->errorResponse('Error al crear resúmenes diarios', $e);
        }
    }

    /**
     * Crear resúmenes diarios automáticamente para todas las fechas pendientes
     */
    public function createAllPendingSummaries(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:companies,id',
                'branch_id' => 'required|integer|exists:branches,id',
                'incluir_vencidas' => 'nullable|boolean', // Si incluir fechas fuera del plazo
                'forzar_envio' => 'nullable|boolean', // Si forzar creación aunque esté vencido
            ]);

            // Obtener fechas pendientes
            $fechasPendientes = $this->documentService->getFechasBoletasPendientes(
                $validated['company_id'],
                $validated['branch_id']
            );

            // Filtrar según preferencia
            $incluirVencidas = $validated['incluir_vencidas'] ?? false;

            if (!$incluirVencidas) {
                $fechasPendientes = array_filter($fechasPendientes, fn($f) => $f['dentro_del_plazo']);
            }

            if (empty($fechasPendientes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay fechas pendientes para crear resúmenes diarios'
                ], 404);
            }

            $resultados = [];
            $exitosos = 0;
            $fallidos = 0;

            foreach ($fechasPendientes as $fechaInfo) {
                try {
                    $summaryData = [
                        'company_id' => $validated['company_id'],
                        'branch_id' => $validated['branch_id'],
                        'fecha_resumen' => $fechaInfo['fecha_emision'],
                        'ubl_version' => '2.1',
                        'moneda' => 'PEN',
                        'forzar_envio' => $validated['forzar_envio'] ?? false,
                    ];

                    $summary = $this->documentService->createSummaryFromBoletas($summaryData);

                    $resultados[] = [
                        'fecha' => $fechaInfo['fecha_emision'],
                        'success' => true,
                        'resumen_id' => $summary->id,
                        'numero_completo' => $summary->numero_completo,
                        'total_boletas' => $summary->boletas()->count(),
                        'dias_transcurridos' => $fechaInfo['dias_transcurridos'],
                        'vencido' => $fechaInfo['vencido'],
                        'message' => $fechaInfo['vencido']
                            ? 'Resumen creado (ADVERTENCIA: Fecha fuera del plazo de 3 días)'
                            : 'Resumen creado correctamente'
                    ];

                    $exitosos++;

                } catch (Exception $e) {
                    $resultados[] = [
                        'fecha' => $fechaInfo['fecha_emision'],
                        'success' => false,
                        'total_boletas' => $fechaInfo['total_boletas'],
                        'dias_transcurridos' => $fechaInfo['dias_transcurridos'],
                        'vencido' => $fechaInfo['vencido'],
                        'error' => $e->getMessage(),
                        'message' => 'Error al crear resumen'
                    ];

                    $fallidos++;

                    \Log::warning('Error al crear resumen diario automático', [
                        'fecha' => $fechaInfo['fecha_emision'],
                        'company_id' => $validated['company_id'],
                        'branch_id' => $validated['branch_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => $exitosos > 0,
                'data' => $resultados,
                'resumen' => [
                    'total_fechas' => count($fechasPendientes),
                    'exitosos' => $exitosos,
                    'fallidos' => $fallidos
                ],
                'message' => $fallidos > 0
                    ? "Se crearon {$exitosos} resúmenes correctamente, {$fallidos} fallaron"
                    : "Se crearon {$exitosos} resúmenes diarios correctamente"
            ], $exitosos > 0 ? 201 : 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return $this->errorResponse('Error al crear resúmenes automáticos', $e);
        }
    }

    /**
     * Anular boletas localmente (sin enviar a SUNAT)
     * Útil para boletas vencidas que no pueden enviarse
     */
    public function anularBoletasLocalmente(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'boletas_ids' => 'required|array|min:1',
                'boletas_ids.*' => 'required|integer|exists:boletas,id',
                'motivo' => 'required|string|max:100',
                'observaciones' => 'nullable|string|max:500',
                'usuario_id' => 'nullable|integer',
            ]);

            $anuladas = 0;
            $errores = [];

            foreach ($validated['boletas_ids'] as $boletaId) {
                try {
                    $boleta = Boleta::findOrFail($boletaId);

                    // Validar que no esté ya anulada
                    if ($boleta->anulada_localmente) {
                        $errores[] = [
                            'boleta_id' => $boletaId,
                            'numero_completo' => $boleta->numero_completo,
                            'error' => 'La boleta ya está anulada localmente'
                        ];
                        continue;
                    }

                    // Validar que no esté aceptada por SUNAT
                    if ($boleta->estado_sunat === 'ACEPTADO') {
                        $errores[] = [
                            'boleta_id' => $boletaId,
                            'numero_completo' => $boleta->numero_completo,
                            'error' => 'No se puede anular localmente una boleta ya aceptada por SUNAT'
                        ];
                        continue;
                    }

                    // Anular localmente
                    $boleta->update([
                        'anulada_localmente' => true,
                        'motivo_anulacion_local' => $validated['motivo'],
                        'observaciones_anulacion' => $validated['observaciones'] ?? null,
                        'fecha_anulacion_local' => now(),
                        'usuario_anulacion_id' => $validated['usuario_id'] ?? null,
                    ]);

                    $anuladas++;

                    \Log::info('Boleta anulada localmente', [
                        'boleta_id' => $boletaId,
                        'numero_completo' => $boleta->numero_completo,
                        'motivo' => $validated['motivo'],
                        'usuario_id' => $validated['usuario_id'] ?? null
                    ]);

                } catch (Exception $e) {
                    $errores[] = [
                        'boleta_id' => $boletaId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => $anuladas > 0,
                'data' => [
                    'anuladas' => $anuladas,
                    'errores' => $errores,
                    'total_procesadas' => count($validated['boletas_ids'])
                ],
                'message' => $anuladas > 0
                    ? "Se anularon {$anuladas} boletas localmente" . (count($errores) > 0 ? ", " . count($errores) . " con errores" : "")
                    : "No se pudo anular ninguna boleta"
            ], $anuladas > 0 ? 200 : 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular boletas localmente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reporte de boletas vencidas no enviadas
     */
    public function getBoletasVencidas(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:companies,id',
                'branch_id' => 'required|integer|exists:branches,id',
                'incluir_anuladas' => 'nullable|in:true,false,1,0',
            ]);

            // Convertir string a boolean
            if (isset($validated['incluir_anuladas'])) {
                $validated['incluir_anuladas'] = filter_var($validated['incluir_anuladas'], FILTER_VALIDATE_BOOLEAN);
            }

            $hoy = now();
            $fechaLimite = $hoy->copy()->subDays(3)->startOfDay();

            $query = Boleta::with(['company', 'branch', 'client'])
                ->where('company_id', $validated['company_id'])
                ->where('branch_id', $validated['branch_id'])
                ->where('estado_sunat', 'PENDIENTE')
                ->withoutSummary()
                ->forSummary()
                ->where('fecha_emision', '<', $fechaLimite);

            // Filtrar anuladas localmente si se requiere
            if (!($validated['incluir_anuladas'] ?? false)) {
                $query->noAnuladaLocalmente();
            }

            $boletasVencidas = $query->orderBy('fecha_emision', 'desc')
                ->get()
                ->map(function ($boleta) use ($hoy) {
                    $fechaEmision = $boleta->fecha_emision;
                    $diasTranscurridos = $fechaEmision->diffInDays($hoy, false);
                    $diasVencidos = $diasTranscurridos - 3;

                    return [
                        'id' => $boleta->id,
                        'numero_completo' => $boleta->numero_completo,
                        'serie' => $boleta->serie,
                        'correlativo' => $boleta->correlativo,
                        'fecha_emision' => $boleta->fecha_emision->format('Y-m-d'),
                        'monto' => $boleta->mto_imp_venta,
                        'cliente' => $boleta->client ? [
                            'tipo_documento' => $boleta->client->tipo_documento,
                            'numero_documento' => $boleta->client->numero_documento,
                            'nombre' => $boleta->client->razon_social ?? $boleta->client->nombre_completo
                        ] : null,
                        'dias_transcurridos' => $diasTranscurridos,
                        'dias_vencidos' => $diasVencidos,
                        'fecha_limite_envio' => $fechaEmision->copy()->addDays(3)->format('Y-m-d'),
                        'anulada_localmente' => $boleta->anulada_localmente,
                        'motivo_anulacion_local' => $boleta->motivo_anulacion_local,
                        'observaciones_anulacion' => $boleta->observaciones_anulacion,
                        'fecha_anulacion_local' => $boleta->fecha_anulacion_local?->format('Y-m-d H:i:s'),
                    ];
                });

            // Agrupar por estado
            $noAnuladas = $boletasVencidas->where('anulada_localmente', false);
            $anuladas = $boletasVencidas->where('anulada_localmente', true);

            return response()->json([
                'success' => true,
                'data' => [
                    'todas' => $boletasVencidas->values(),
                    'no_anuladas' => $noAnuladas->values(),
                    'anuladas_localmente' => $anuladas->values(),
                ],
                'resumen' => [
                    'total_vencidas' => $boletasVencidas->count(),
                    'no_anuladas' => $noAnuladas->count(),
                    'anuladas_localmente' => $anuladas->count(),
                    'monto_total_vencido' => $boletasVencidas->sum('monto'),
                    'monto_no_anulado' => $noAnuladas->sum('monto'),
                ],
                'advertencia' => 'Las boletas vencidas (más de 3 días) NO pueden enviarse a SUNAT mediante resumen diario. Deben anularse localmente o emitirse nuevas boletas.',
                'message' => "Se encontraron {$boletasVencidas->count()} boletas vencidas"
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener boletas vencidas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar resumen a SUNAT
     */
    public function sendSummaryToSunat(string $summaryId): JsonResponse
    {
        try {
            $summary = DailySummary::with(['company', 'branch', 'boletas'])->findOrFail($summaryId);

            if ($summary->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'El resumen ya fue aceptado por SUNAT'
                ], 400);
            }

            $result = $this->documentService->sendDailySummaryToSunat($summary);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch', 'boletas']),
                    'ticket' => $result['ticket'],
                    'message' => 'Resumen enviado correctamente a SUNAT'
                ]);
            }

            return response()->json([
                'success' => false,
                'data' => $result['document']->load(['company', 'branch', 'boletas']),
                'message' => 'Error al enviar resumen a SUNAT',
                'error' => $result['error']
            ], 400);

        } catch (Exception $e) {
            return $this->errorResponse('Error interno al enviar resumen', $e);
        }
    }

    /**
     * Consultar estado de resumen
     */
    public function checkSummaryStatus(string $summaryId): JsonResponse
    {
        try {
            $summary = DailySummary::with(['company', 'branch', 'boletas'])->findOrFail($summaryId);
            $result = $this->documentService->checkSummaryStatus($summary);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch', 'boletas']),
                    'message' => 'Estado del resumen consultado correctamente'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado: ' . ($result['error'] ?? 'Error desconocido')
            ], 400);

        } catch (Exception $e) {
            return $this->errorResponse('Error al consultar estado del resumen', $e);
        }
    }

    /**
     * Obtener boletas pendientes para resumen
     */
    public function getBoletsasPendingForSummary(GetBoletasPendingRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $boletas = $this->getPendingBoletas($validated);

            // Calcular información del plazo
            $fechaEmision = \Carbon\Carbon::parse($validated['fecha_emision']);
            $hoy = now();
            $diasTranscurridos = $fechaEmision->diffInDays($hoy, false);
            $diasRestantes = 3 - $diasTranscurridos;
            $dentroDelPlazo = $diasRestantes >= 0;

            return response()->json([
                'success' => true,
                'data' => $boletas,
                'total' => $boletas->count(),
                'plazo' => [
                    'fecha_emision' => $fechaEmision->format('Y-m-d'),
                    'dias_transcurridos' => $diasTranscurridos,
                    'dias_restantes' => max(0, $diasRestantes),
                    'dentro_del_plazo' => $dentroDelPlazo,
                    'urgente' => $diasRestantes <= 1,
                    'vencido' => !$dentroDelPlazo,
                    'fecha_limite_envio' => $fechaEmision->copy()->addDays(3)->format('Y-m-d'),
                    'mensaje' => $dentroDelPlazo
                        ? ($diasRestantes <= 1
                            ? "⚠️ URGENTE: Solo quedan {$diasRestantes} día(s) para enviar el resumen"
                            : "Quedan {$diasRestantes} días para enviar el resumen")
                        : "❌ VENCIDO: El plazo de 3 días ya expiró. Las boletas no podrán ser incluidas en resumen diario."
                ],
                'normativa' => 'RS N° 000003-2023/SUNAT - Plazo máximo: 3 días calendario desde el día siguiente a la emisión',
                'message' => 'Boletas pendientes obtenidas correctamente'
            ]);

        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener boletas pendientes', $e);
        }
    }

    /**
     * Aplicar filtros a la consulta
     */
    private function applyFilters($query, Request $request): void
    {
        $filters = [
            'company_id' => 'where',
            'branch_id' => 'where',
            'estado_sunat' => 'where',
            'fecha_desde' => 'whereDate|>=',
            'fecha_hasta' => 'whereDate|<='
        ];

        foreach ($filters as $field => $operation) {
            if ($request->has($field)) {
                $parts = explode('|', $operation);
                $method = $parts[0];
                $operator = $parts[1] ?? null;

                if ($operator) {
                    $query->$method('fecha_emision', $operator, $request->$field);
                } else {
                    $query->$method($field, $request->$field);
                }
            }
        }
    }

    /**
     * Obtener boletas pendientes
     */
    private function getPendingBoletas(array $filters)
    {
        return Boleta::with(['company', 'branch', 'client'])
            ->where('company_id', $filters['company_id'])
            ->where('branch_id', $filters['branch_id'])
            ->whereDate('fecha_emision', $filters['fecha_emision'])
            ->pending()
            ->withoutSummary()
            ->forSummary() // Excluir boletas con envío individual
            ->get();
    }

    /**
     * Manejar error de SUNAT
     */
    private function handleSunatError(array $result): JsonResponse
    {
        $error = $result['error'];
        $errorCode = 'UNKNOWN';
        $errorMessage = 'Error desconocido';

        if (is_object($error)) {
            $errorCode = method_exists($error, 'getCode') ? $error->getCode() : ($error->code ?? $errorCode);
            $errorMessage = method_exists($error, 'getMessage') ? $error->getMessage() : ($error->message ?? $errorMessage);
        }

        return response()->json([
            'success' => false,
            'data' => $result['document'],
            'message' => 'Error al enviar a SUNAT: ' . $errorMessage,
            'error_code' => $errorCode
        ], 400);
    }

    /**
     * Obtener datos de paginación
     */
    private function getPaginationData($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Respuesta de error estandarizada
     */
    private function errorResponse(string $message, Exception $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message . ': ' . $e->getMessage()
        ], 500);
    }

    /**
     * Respuesta de no encontrado
     */
    private function notFoundResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 404);
    }

    /**
     * Obtener boletas que pueden ser anuladas
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBoletasAnulables(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:companies,id',
                'branch_id' => 'required|integer|exists:branches,id',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date',
            ]);

            $hoy = now();
            $fechaLimite = $hoy->copy()->subDays(3)->startOfDay(); // Límite de 3 días

            $query = Boleta::with(['company', 'branch', 'client', 'dailySummary'])
                ->where('company_id', $validated['company_id'])
                ->where('branch_id', $validated['branch_id'])
                ->anulable() // Scope: estado_sunat = ACEPTADO, estado_anulacion = sin_anular, anulada_localmente = false
                ->where('fecha_emision', '>=', $fechaLimite) // Solo boletas dentro del plazo
                ->forSummary(); // Excluir boletas con envío individual

            // Filtros opcionales de fecha
            if (isset($validated['fecha_desde'])) {
                $query->where('fecha_emision', '>=', $validated['fecha_desde']);
            }

            if (isset($validated['fecha_hasta'])) {
                $query->where('fecha_emision', '<=', $validated['fecha_hasta']);
            }

            $boletas = $query->orderBy('fecha_emision', 'desc')
                ->get()
                ->map(function ($boleta) use ($hoy) {
                    $fechaEmision = \Carbon\Carbon::parse($boleta->fecha_emision);
                    $diasTranscurridos = $fechaEmision->diffInDays($hoy, false);
                    $diasRestantes = 3 - $diasTranscurridos;

                    return [
                        'id' => $boleta->id,
                        'numero_completo' => $boleta->numero_completo,
                        'serie' => $boleta->serie,
                        'correlativo' => $boleta->correlativo,
                        'fecha_emision' => $boleta->fecha_emision->format('Y-m-d'),
                        'cliente' => [
                            'tipo_documento' => $boleta->client->tipo_documento ?? null,
                            'numero_documento' => $boleta->client->numero_documento ?? null,
                            'nombre' => $boleta->client->razon_social ?? $boleta->client->nombre_completo ?? null,
                        ],
                        'moneda' => $boleta->moneda,
                        'mto_imp_venta' => $boleta->mto_imp_venta,
                        'estado_sunat' => $boleta->estado_sunat,
                        'estado_anulacion' => $boleta->estado_anulacion,
                        'dias_transcurridos' => $diasTranscurridos,
                        'dias_restantes' => $diasRestantes,
                        'urgente' => $diasRestantes <= 1,
                        'fecha_limite_anulacion' => $fechaEmision->copy()->addDays(3)->format('Y-m-d'),
                    ];
                });

            // Agrupar por fecha de emisión
            $boletasPorFecha = $boletas->groupBy('fecha_emision')->map(function ($items, $fecha) {
                return [
                    'fecha_emision' => $fecha,
                    'total_boletas' => $items->count(),
                    'monto_total' => $items->sum('mto_imp_venta'),
                    'dias_restantes' => $items->first()['dias_restantes'],
                    'urgente' => $items->first()['urgente'],
                    'boletas' => $items->values()
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_boletas' => $boletas->count(),
                    'monto_total' => $boletas->sum('mto_imp_venta'),
                    'por_fecha' => $boletasPorFecha,
                    'boletas' => $boletas->values(),
                ],
                'message' => 'Boletas anulables obtenidas exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error('Error al obtener boletas anulables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener boletas anulables: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular boletas mediante resumen diario (estado = 3)
     *
     * Este endpoint facilita la creación de un resumen diario de anulación
     * construyendo automáticamente el payload para el endpoint de daily-summaries.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function anularBoletasOficialmente(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:companies,id',
                'branch_id' => 'required|integer|exists:branches,id',

                // Opción 1: Array simple de IDs con motivo único
                'boletas_ids' => 'required_without:boletas|array|min:1',
                'boletas_ids.*' => 'integer|exists:boletas,id',
                'motivo_anulacion' => 'required_without:boletas|string|max:100',

                // Opción 2: Array detallado con motivo individual por boleta
                'boletas' => 'required_without:boletas_ids|array|min:1',
                'boletas.*.id' => 'required|integer|exists:boletas,id',
                'boletas.*.motivo' => 'required|string|max:100',

                'usuario_id' => 'nullable|integer',
            ]);

            // Normalizar datos: convertir a formato unificado
            $boletasData = $this->normalizarBoletasAnulacion($validated);

            // Extraer IDs de boletas
            $boletasIds = array_column($boletasData, 'id');

            // Obtener las boletas a anular
            $boletas = Boleta::with('client')
                ->whereIn('id', $boletasIds)
                ->where('company_id', $validated['company_id'])
                ->where('branch_id', $validated['branch_id'])
                ->get();

            if ($boletas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron boletas para anular'
                ], 404);
            }

            // Validaciones
            $hoy = now();
            foreach ($boletas as $boleta) {
                // Validar estado ACEPTADO
                if ($boleta->estado_sunat !== 'ACEPTADO') {
                    return response()->json([
                        'success' => false,
                        'message' => "La boleta {$boleta->numero_completo} no está aceptada por SUNAT. Solo se pueden anular boletas aceptadas."
                    ], 400);
                }

                // Validar no anulada localmente
                if ($boleta->anulada_localmente) {
                    return response()->json([
                        'success' => false,
                        'message' => "La boleta {$boleta->numero_completo} ya fue anulada localmente."
                    ], 400);
                }

                // Validar no ya anulada/pendiente
                if (in_array($boleta->estado_anulacion, ['pendiente_anulacion', 'anulada'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "La boleta {$boleta->numero_completo} ya está {$boleta->estado_anulacion}."
                    ], 400);
                }

                // Validar plazo de 3 días
                $fechaEmision = \Carbon\Carbon::parse($boleta->fecha_emision);
                $diasTranscurridos = $fechaEmision->diffInDays($hoy, false);

                if ($diasTranscurridos > 3) {
                    return response()->json([
                        'success' => false,
                        'message' => "La boleta {$boleta->numero_completo} está fuera del plazo de 3 días (emitida el {$fechaEmision->format('Y-m-d')}). Use la anulación local para boletas vencidas.",
                        'dias_transcurridos' => $diasTranscurridos
                    ], 400);
                }
            }

            // Validar que todas sean de la misma fecha
            $fechas = $boletas->pluck('fecha_emision')->map(fn($f) => $f->format('Y-m-d'))->unique();
            if ($fechas->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => "Las boletas seleccionadas tienen diferentes fechas de emisión ({$fechas->implode(', ')}). Debe crear un resumen de anulación por cada fecha."
                ], 400);
            }

            $fechaResumen = $boletas->first()->fecha_emision->format('Y-m-d');

            // Construir detalles con estado="3" (anulación)
            $detalles = [];
            foreach ($boletas as $boleta) {
                $detalles[] = [
                    'tipo_documento' => $boleta->tipo_documento,
                    'serie_numero' => $boleta->serie . '-' . $boleta->correlativo,
                    'estado' => '3', // Estado 3 = Anulación
                    'cliente_tipo' => $boleta->client->tipo_documento ?? '1',
                    'cliente_numero' => $boleta->client->numero_documento ?? '00000000',
                    'total' => $boleta->mto_imp_venta,
                    'mto_oper_gravadas' => $boleta->mto_oper_gravadas ?? 0,
                    'mto_oper_exoneradas' => $boleta->mto_oper_exoneradas ?? 0,
                    'mto_oper_inafectas' => $boleta->mto_oper_inafectas ?? 0,
                    'mto_oper_gratuitas' => $boleta->mto_oper_gratuitas ?? 0,
                    'mto_igv' => $boleta->mto_igv ?? 0,
                    'mto_isc' => $boleta->mto_isc ?? 0,
                    'mto_icbper' => $boleta->mto_icbper ?? 0,
                ];
            }

            // Crear resumen usando el endpoint existente de daily-summaries
            $summaryData = [
                'company_id' => $validated['company_id'],
                'branch_id' => $validated['branch_id'],
                'fecha_generacion' => now()->toDateString(),
                'fecha_resumen' => $fechaResumen,
                'ubl_version' => '2.1',
                'moneda' => $boletas->first()->moneda ?? 'PEN',
                'detalles' => $detalles,
                'usuario_creacion' => $validated['usuario_id'] ?? 'sistema',
            ];

            $summary = $this->documentService->createDailySummary($summaryData);

            // Crear mapa de motivos por ID de boleta
            $motivosPorId = collect($boletasData)->pluck('motivo', 'id')->toArray();

            // Marcar boletas como pendientes de anulación con su motivo individual
            foreach ($boletas as $boleta) {
                $boleta->update([
                    'estado_anulacion' => 'pendiente_anulacion',
                    'motivo_anulacion' => $motivosPorId[$boleta->id],
                    'fecha_solicitud_anulacion' => now(),
                    'usuario_solicitud_anulacion_id' => $validated['usuario_id'] ?? null,
                    'daily_summary_id' => $summary->id,
                ]);
            }

            Log::info('Resumen de anulación creado', [
                'summary_id' => $summary->id,
                'boletas_count' => $boletas->count(),
                'boletas_ids' => $boletas->pluck('id')->toArray(),
                'motivos_por_boleta' => $motivosPorId
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'id' => $summary->id,
                        'numero_completo' => $summary->numero_completo,
                        'fecha_resumen' => $summary->fecha_resumen,
                        'fecha_generacion' => $summary->fecha_generacion,
                        'correlativo' => $summary->correlativo,
                        'estado_proceso' => $summary->estado_proceso,
                        'estado_sunat' => $summary->estado_sunat,
                    ],
                    'boletas_count' => $boletas->count(),
                    'boletas_ids' => $boletas->pluck('id')->toArray(),
                ],
                'message' => 'Resumen de anulación creado exitosamente. Use POST /api/v1/daily-summaries/' . $summary->id . '/send-sunat para enviar a SUNAT.'
            ], 201);

        } catch (Exception $e) {
            Log::error('Error al anular boletas oficialmente', [
                'error' => $e->getMessage(),
                'request_data' => $validated ?? [],
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al anular boletas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener boletas pendientes de anulación (esperando confirmación de SUNAT)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBoletasPendientesAnulacion(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:companies,id',
                'branch_id' => 'required|integer|exists:branches,id',
            ]);

            $boletas = Boleta::with(['company', 'branch', 'client', 'dailySummary'])
                ->where('company_id', $validated['company_id'])
                ->where('branch_id', $validated['branch_id'])
                ->pendienteAnulacion()
                ->orderBy('fecha_solicitud_anulacion', 'desc')
                ->get()
                ->map(function ($boleta) {
                    return [
                        'id' => $boleta->id,
                        'numero_completo' => $boleta->numero_completo,
                        'fecha_emision' => $boleta->fecha_emision->format('Y-m-d'),
                        'cliente' => [
                            'nombre' => $boleta->client->razon_social ?? $boleta->client->nombre_completo ?? null,
                        ],
                        'mto_imp_venta' => $boleta->mto_imp_venta,
                        'estado_anulacion' => $boleta->estado_anulacion,
                        'motivo_anulacion' => $boleta->motivo_anulacion,
                        'fecha_solicitud_anulacion' => $boleta->fecha_solicitud_anulacion?->format('Y-m-d H:i:s'),
                        'resumen_diario' => $boleta->dailySummary ? [
                            'id' => $boleta->dailySummary->id,
                            'numero_completo' => $boleta->dailySummary->numero_completo,
                            'estado_proceso' => $boleta->dailySummary->estado_proceso,
                            'estado_sunat' => $boleta->dailySummary->estado_sunat,
                            'ticket' => $boleta->dailySummary->ticket,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $boletas->count(),
                    'boletas' => $boletas
                ],
                'message' => 'Boletas pendientes de anulación obtenidas exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error('Error al obtener boletas pendientes de anulación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener boletas pendientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener boletas anuladas oficialmente
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBoletasAnuladas(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:companies,id',
                'branch_id' => 'required|integer|exists:branches,id',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date',
            ]);

            $query = Boleta::with(['company', 'branch', 'client', 'dailySummary'])
                ->where('company_id', $validated['company_id'])
                ->where('branch_id', $validated['branch_id'])
                ->anulada(); // Scope: estado_anulacion = anulada

            // Filtros opcionales
            if (isset($validated['fecha_desde'])) {
                $query->where('fecha_emision', '>=', $validated['fecha_desde']);
            }

            if (isset($validated['fecha_hasta'])) {
                $query->where('fecha_emision', '<=', $validated['fecha_hasta']);
            }

            $boletas = $query->orderBy('fecha_solicitud_anulacion', 'desc')
                ->get()
                ->map(function ($boleta) {
                    return [
                        'id' => $boleta->id,
                        'numero_completo' => $boleta->numero_completo,
                        'fecha_emision' => $boleta->fecha_emision->format('Y-m-d'),
                        'cliente' => [
                            'nombre' => $boleta->client->razon_social ?? $boleta->client->nombre_completo ?? null,
                        ],
                        'mto_imp_venta' => $boleta->mto_imp_venta,
                        'estado_sunat' => $boleta->estado_sunat,
                        'estado_anulacion' => $boleta->estado_anulacion,
                        'motivo_anulacion' => $boleta->motivo_anulacion,
                        'fecha_solicitud_anulacion' => $boleta->fecha_solicitud_anulacion?->format('Y-m-d H:i:s'),
                        'resumen_diario' => $boleta->dailySummary ? [
                            'id' => $boleta->dailySummary->id,
                            'numero_completo' => $boleta->dailySummary->numero_completo,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $boletas->count(),
                    'monto_total' => $boletas->sum('mto_imp_venta'),
                    'boletas' => $boletas
                ],
                'message' => 'Boletas anuladas obtenidas exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error('Error al obtener boletas anuladas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener boletas anuladas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalizar datos de boletas para anulación
     * Convierte ambos formatos (simple y detallado) a un formato unificado
     *
     * @param array $validated
     * @return array Array con formato: [['id' => 1, 'motivo' => 'texto'], ...]
     */
    private function normalizarBoletasAnulacion(array $validated): array
    {
        // Formato 1: Array simple con motivo único para todas
        if (isset($validated['boletas_ids'])) {
            $motivoUnico = $validated['motivo_anulacion'];
            return array_map(function($id) use ($motivoUnico) {
                return [
                    'id' => $id,
                    'motivo' => $motivoUnico
                ];
            }, $validated['boletas_ids']);
        }

        // Formato 2: Array detallado con motivo individual por boleta
        if (isset($validated['boletas'])) {
            return array_map(function($boleta) {
                return [
                    'id' => $boleta['id'],
                    'motivo' => $boleta['motivo']
                ];
            }, $validated['boletas']);
        }

        // Fallback (no debería llegar aquí por la validación)
        return [];
    }
}