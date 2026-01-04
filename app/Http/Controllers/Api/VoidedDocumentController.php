<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VoidedDocument;
use App\Models\VoidedReason;
use App\Services\DocumentService;
use App\Services\FileService;
use App\Http\Requests\StoreVoidedDocumentRequest;
use App\Http\Requests\IndexVoidedDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VoidedDocumentController extends Controller
{
    protected $documentService;
    protected $fileService;

    public function __construct(DocumentService $documentService, FileService $fileService)
    {
        $this->documentService = $documentService;
        $this->fileService = $fileService;
    }

    public function index(IndexVoidedDocumentRequest $request): JsonResponse
    {
        try {
            $query = VoidedDocument::with(['company', 'branch']);

            // Filtros
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('estado_sunat')) {
                $query->where('estado_sunat', $request->estado_sunat);
            }

            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $query->whereBetween('fecha_emision', [
                    $request->fecha_desde,
                    $request->fecha_hasta
                ]);
            }

            if ($request->has('fecha_referencia')) {
                $query->where('fecha_referencia', $request->fecha_referencia);
            }

            $perPage = $request->get('per_page', 15);
            $voidedDocuments = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $voidedDocuments->items(),
                'pagination' => [
                    'current_page' => $voidedDocuments->currentPage(),
                    'last_page' => $voidedDocuments->lastPage(),
                    'per_page' => $voidedDocuments->perPage(),
                    'total' => $voidedDocuments->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener comunicaciones de baja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreVoidedDocumentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $voidedDocument = $this->documentService->createVoidedDocument($validated);

            return response()->json([
                'success' => true,
                'data' => $voidedDocument->load(['company', 'branch']),
                'message' => 'Comunicación de baja creada correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear comunicación de baja: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $voidedDocument = VoidedDocument::with(['company', 'branch'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $voidedDocument
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comunicación de baja no encontrada'
            ], 404);
        }
    }

    public function sendToSunat(string $id): JsonResponse
    {
        try {
            $voidedDocument = VoidedDocument::with(['company', 'branch'])->findOrFail($id);

            if ($voidedDocument->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'La comunicación de baja ya fue aceptada por SUNAT'
                ], 400);
            }

            $result = $this->documentService->sendVoidedDocumentToSunat($voidedDocument);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch']),
                    'ticket' => $result['ticket'],
                    'message' => 'Comunicación de baja enviada correctamente a SUNAT'
                ]);
            } else {
                // Manejar diferentes tipos de error
                $errorCode = 'UNKNOWN';
                $errorMessage = 'Error desconocido';

                if (is_object($result['error'])) {
                    if (method_exists($result['error'], 'getCode')) {
                        $errorCode = $result['error']->getCode();
                    } elseif (property_exists($result['error'], 'code')) {
                        $errorCode = $result['error']->code;
                    }

                    if (method_exists($result['error'], 'getMessage')) {
                        $errorMessage = $result['error']->getMessage();
                    } elseif (property_exists($result['error'], 'message')) {
                        $errorMessage = $result['error']->message;
                    }
                }

                return response()->json([
                    'success' => false,
                    'data' => $result['document']->load(['company', 'branch']),
                    'message' => 'Error al enviar a SUNAT: ' . $errorMessage,
                    'error_code' => $errorCode
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkStatus(string $id): JsonResponse
    {
        try {
            $voidedDocument = VoidedDocument::with(['company', 'branch'])->findOrFail($id);

            if (empty($voidedDocument->ticket)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay ticket para consultar el estado'
                ], 400);
            }

            $result = $this->documentService->checkVoidedDocumentStatus($voidedDocument);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch']),
                    'message' => 'Estado consultado correctamente'
                ]);
            } else {
                $errorMessage = 'Error desconocido';
                if (isset($result['error'])) {
                    if (is_object($result['error']) && method_exists($result['error'], 'getMessage')) {
                        $errorMessage = $result['error']->getMessage();
                    } elseif (is_string($result['error'])) {
                        $errorMessage = $result['error'];
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Error al consultar estado: ' . $errorMessage
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadXml(string $id): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $voidedDocument = VoidedDocument::findOrFail($id);

            // Verificar que el documento haya sido enviado a SUNAT
            if ($voidedDocument->estado_sunat === 'PENDIENTE') {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento aún no ha sido enviado a SUNAT. Debe enviar el documento primero.'
                ], 400);
            }

            // Verificar que exista el xml_path
            if (empty($voidedDocument->xml_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento no tiene un XML asociado. Esto puede ocurrir si el envío a SUNAT falló.',
                    'estado_sunat' => $voidedDocument->estado_sunat
                ], 404);
            }

            // Construir la ruta completa del archivo
            $fullPath = storage_path('app/public/' . $voidedDocument->xml_path);

            // Verificar que el archivo exista físicamente
            if (!file_exists($fullPath)) {
                \Log::error('XML no encontrado en el sistema de archivos', [
                    'voided_document_id' => $id,
                    'xml_path' => $voidedDocument->xml_path,
                    'full_path' => $fullPath,
                    'estado_sunat' => $voidedDocument->estado_sunat
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'El archivo XML no se encuentra en el servidor. Ruta esperada: ' . $voidedDocument->xml_path,
                    'estado_sunat' => $voidedDocument->estado_sunat,
                    'suggestion' => 'Intente reenviar el documento a SUNAT o contacte al administrador.'
                ], 404);
            }

            return response()->download(
                $fullPath,
                $voidedDocument->identificador . '.xml',
                ['Content-Type' => 'application/xml']
            );

        } catch (\Exception $e) {
            \Log::error('Error al descargar XML de comunicación de baja', [
                'voided_document_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar XML: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadCdr(string $id): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $voidedDocument = VoidedDocument::findOrFail($id);

            // Verificar que el documento haya sido aceptado por SUNAT
            if ($voidedDocument->estado_sunat !== 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'El CDR solo está disponible cuando el documento ha sido ACEPTADO por SUNAT.',
                    'estado_actual' => $voidedDocument->estado_sunat,
                    'suggestion' => $voidedDocument->estado_sunat === 'ENVIADO'
                        ? 'Consulte el estado del documento primero usando el endpoint check-status.'
                        : 'El documento debe ser enviado y aceptado por SUNAT primero.'
                ], 400);
            }

            // Verificar que exista el cdr_path
            if (empty($voidedDocument->cdr_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento no tiene un CDR asociado.',
                    'estado_sunat' => $voidedDocument->estado_sunat
                ], 404);
            }

            // Construir la ruta completa del archivo
            $fullPath = storage_path('app/public/' . $voidedDocument->cdr_path);

            // Verificar que el archivo exista físicamente
            if (!file_exists($fullPath)) {
                \Log::error('CDR no encontrado en el sistema de archivos', [
                    'voided_document_id' => $id,
                    'cdr_path' => $voidedDocument->cdr_path,
                    'full_path' => $fullPath,
                    'estado_sunat' => $voidedDocument->estado_sunat
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'El archivo CDR no se encuentra en el servidor. Ruta esperada: ' . $voidedDocument->cdr_path,
                    'estado_sunat' => $voidedDocument->estado_sunat,
                    'suggestion' => 'Intente consultar el estado nuevamente usando check-status o contacte al administrador.'
                ], 404);
            }

            return response()->download(
                $fullPath,
                'R-' . $voidedDocument->identificador . '.zip',
                ['Content-Type' => 'application/zip']
            );

        } catch (\Exception $e) {
            \Log::error('Error al descargar CDR de comunicación de baja', [
                'voided_document_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar CDR: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDocumentsForVoiding(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'company_id' => 'required|exists:companies,id',
                'branch_id' => 'required|exists:branches,id',
                'fecha_referencia' => 'required|date',
                'tipo_documento' => 'nullable|in:01,07,08',
                'agrupar_por_tipo' => 'nullable|boolean'
            ]);

            $documents = $this->documentService->getDocumentsForVoiding(
                $request->company_id,
                $request->branch_id,
                $request->fecha_referencia,
                $request->tipo_documento
            );

            // Validar plazo de 7 días
            $fechaReferencia = \Carbon\Carbon::parse($request->fecha_referencia);
            $diasTranscurridos = $fechaReferencia->diffInDays(\Carbon\Carbon::now());
            $dentroDelPlazo = $diasTranscurridos <= 7;

            // Agrupar por tipo si se solicita
            if ($request->get('agrupar_por_tipo', false)) {
                $grouped = collect($documents)->groupBy('tipo_documento')->map(function($items, $tipo) {
                    return [
                        'tipo_documento' => $tipo,
                        'tipo_nombre' => $items->first()['tipo_nombre'] ?? '',
                        'documentos' => $items->values(),
                        'total' => $items->count(),
                        'monto_total' => $items->sum('monto')
                    ];
                })->values();

                return response()->json([
                    'success' => true,
                    'data' => $grouped,
                    'total' => count($documents),
                    'total_tipos' => $grouped->count(),
                    'fecha_referencia' => $request->fecha_referencia,
                    'dias_transcurridos' => $diasTranscurridos,
                    'dentro_del_plazo' => $dentroDelPlazo,
                    'plazo_restante' => $dentroDelPlazo ? (7 - $diasTranscurridos) : 0,
                    'advertencia' => !$dentroDelPlazo ? 'Los documentos han superado el plazo de 7 días para comunicación de baja' : null,
                    'message' => 'Documentos disponibles para anulación obtenidos correctamente'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $documents,
                'total' => count($documents),
                'fecha_referencia' => $request->fecha_referencia,
                'dias_transcurridos' => $diasTranscurridos,
                'dentro_del_plazo' => $dentroDelPlazo,
                'plazo_restante' => $dentroDelPlazo ? (7 - $diasTranscurridos) : 0,
                'advertencia' => !$dentroDelPlazo ? 'Los documentos han superado el plazo de 7 días para comunicación de baja' : null,
                'resumen_por_tipo' => [
                    'facturas' => collect($documents)->where('tipo_documento', '01')->count(),
                    'notas_credito' => collect($documents)->where('tipo_documento', '07')->count(),
                    'notas_debito' => collect($documents)->where('tipo_documento', '08')->count(),
                ],
                'message' => 'Documentos disponibles para anulación obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener documentos para anular', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener documentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener catálogo de motivos de baja
     */
    public function getVoidedReasons(Request $request): JsonResponse
    {
        try {
            $query = VoidedReason::active()->ordered();

            // Filtrar por categoría si se proporciona
            if ($request->has('categoria')) {
                $query->byCategory($request->categoria);
            }

            // Buscar por texto
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%");
                });
            }

            $reasons = $query->get();

            // Agrupar por categoría si se solicita
            if ($request->get('agrupar_por_categoria', false)) {
                $grouped = $reasons->groupBy('categoria')->map(function($items, $categoria) {
                    return [
                        'categoria' => $categoria,
                        'categoria_nombre' => $this->getCategoryName($categoria),
                        'motivos' => $items->values()
                    ];
                })->values();

                return response()->json([
                    'success' => true,
                    'data' => $grouped,
                    'total' => $reasons->count(),
                    'total_categorias' => $grouped->count(),
                    'message' => 'Motivos de baja obtenidos correctamente'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $reasons,
                'total' => $reasons->count(),
                'message' => 'Motivos de baja obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener motivos de baja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un motivo de baja por código
     */
    public function getVoidedReasonByCode(string $codigo): JsonResponse
    {
        try {
            $reason = VoidedReason::where('codigo', $codigo)->active()->first();

            if (!$reason) {
                return response()->json([
                    'success' => false,
                    'message' => 'Motivo de baja no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $reason,
                'message' => 'Motivo de baja obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener motivo de baja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener categorías disponibles
     */
    public function getVoidedCategories(): JsonResponse
    {
        try {
            $categories = [
                [
                    'codigo' => 'ERROR_DATOS_CLIENTE',
                    'nombre' => 'Errores en Datos del Cliente',
                    'descripcion' => 'Errores relacionados con RUC, razón social, dirección del cliente'
                ],
                [
                    'codigo' => 'ERROR_DESCRIPCION',
                    'nombre' => 'Errores en Descripción de Productos/Servicios',
                    'descripcion' => 'Errores en descripción, cantidad, unidad de medida, códigos de producto'
                ],
                [
                    'codigo' => 'ERROR_CALCULO',
                    'nombre' => 'Errores en Cálculos',
                    'descripcion' => 'Errores en precios, IGV, totales, tipo de cambio, descuentos'
                ],
                [
                    'codigo' => 'ERROR_TRIBUTARIO',
                    'nombre' => 'Errores Tributarios',
                    'descripcion' => 'Errores en afectación tributaria, tasas de IGV, ICBPER'
                ],
                [
                    'codigo' => 'ERROR_ADMINISTRATIVO',
                    'nombre' => 'Errores Administrativos',
                    'descripcion' => 'Duplicados, emisiones por error, tipo de comprobante incorrecto'
                ],
                [
                    'codigo' => 'OPERACION_NO_REALIZADA',
                    'nombre' => 'Operación No Realizada',
                    'descripcion' => 'Ventas canceladas, servicios no prestados, operaciones no concretadas'
                ],
                [
                    'codigo' => 'ERROR_DOCUMENTO',
                    'nombre' => 'Errores en Documento Físico',
                    'descripcion' => 'Documentos no entregados, perdidos o deteriorados'
                ],
                [
                    'codigo' => 'ERROR_PAGO',
                    'nombre' => 'Errores en Datos de Pago',
                    'descripcion' => 'Errores en forma de pago, medio de pago, plazos'
                ],
                [
                    'codigo' => 'OTROS',
                    'nombre' => 'Otros Motivos',
                    'descripcion' => 'Otros motivos no contemplados en categorías anteriores'
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $categories,
                'total' => count($categories),
                'message' => 'Categorías obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper para obtener nombre de categoría
     */
    private function getCategoryName(string $categoria): string
    {
        return match($categoria) {
            'ERROR_DATOS_CLIENTE' => 'Errores en Datos del Cliente',
            'ERROR_DESCRIPCION' => 'Errores en Descripción de Productos/Servicios',
            'ERROR_CALCULO' => 'Errores en Cálculos',
            'ERROR_TRIBUTARIO' => 'Errores Tributarios',
            'ERROR_ADMINISTRATIVO' => 'Errores Administrativos',
            'OPERACION_NO_REALIZADA' => 'Operación No Realizada',
            'ERROR_DOCUMENTO' => 'Errores en Documento Físico',
            'ERROR_PAGO' => 'Errores en Datos de Pago',
            'OTROS' => 'Otros Motivos',
            default => $categoria
        };
    }
}