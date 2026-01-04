<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesPdfGeneration;
use App\Http\Requests\NotaVenta\StoreNotaVentaRequest;
use App\Services\DocumentService;
use App\Services\FileService;
use App\Models\NotaVenta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NotaVentaController extends Controller
{
    use HandlesPdfGeneration;

    protected $documentService;
    protected $fileService;

    public function __construct(DocumentService $documentService, FileService $fileService)
    {
        $this->documentService = $documentService;
        $this->fileService = $fileService;
    }

    /**
     * Listar Notas de Venta con filtros
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = NotaVenta::with(['company', 'branch', 'client']);

            // Filtros
            if ($request->has('company_id')) {
                $query->byCompany($request->company_id);
            }

            if ($request->has('branch_id')) {
                $query->byBranch($request->branch_id);
            }

            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $query->byDateRange($request->fecha_desde, $request->fecha_hasta);
            }

            if ($request->has('serie')) {
                $query->where('serie', $request->serie);
            }

            if ($request->has('numero_completo')) {
                $query->where('numero_completo', 'like', '%' . $request->numero_completo . '%');
            }

            // Ordenamiento
            $query->recent();

            // Paginación
            $perPage = $request->get('per_page', 15);
            $notasVenta = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $notasVenta,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al listar notas de venta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas de venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear nueva Nota de Venta
     */
    public function store(StoreNotaVentaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $notaVenta = $this->documentService->createNotaVenta($validated);

            return response()->json([
                'success' => true,
                'message' => 'Nota de Venta creada exitosamente',
                'data' => $notaVenta,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear nota de venta', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nota de venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ver detalle de una Nota de Venta
     */
    public function show(int $id): JsonResponse
    {
        try {
            $notaVenta = NotaVenta::with(['company', 'branch', 'client'])
                                  ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $notaVenta,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nota de Venta no encontrada',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Actualizar Nota de Venta
     */
    public function update(StoreNotaVentaRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $notaVenta = $this->documentService->updateNotaVenta($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Nota de Venta actualizada exitosamente',
                'data' => $notaVenta,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar nota de venta', [
                'id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota de venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar Nota de Venta (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $notaVenta = NotaVenta::findOrFail($id);
            $notaVenta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nota de Venta eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota de venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar PDF
     */
    public function generatePdf(int $id, Request $request): JsonResponse
    {
        try {
            $notaVenta = NotaVenta::with(['company', 'branch', 'client'])
                                  ->findOrFail($id);

            return $this->generateDocumentPdf($notaVenta, 'nota-venta', $request);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Descargar PDF
     */
    public function downloadPdf(int $id, Request $request)
    {
        try {
            $notaVenta = NotaVenta::with(['company', 'branch', 'client'])
                                  ->findOrFail($id);

            return $this->downloadDocumentPdf($notaVenta, $request);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
