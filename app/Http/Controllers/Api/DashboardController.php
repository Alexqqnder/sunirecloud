<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\InvoiceRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ClientRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $invoiceRepository;
    protected $companyRepository;
    protected $clientRepository;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        CompanyRepository $companyRepository,
        ClientRepository $clientRepository
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->companyRepository = $companyRepository;
        $this->clientRepository = $clientRepository;
    }

    /**
     * Get dashboard statistics
     *
     * @queryParam company_id int optional ID de la empresa. Si no se envía, muestra todas las empresas.
     * @queryParam branch_id int optional ID de la sucursal. Si no se envía, muestra todas las sucursales.
     * @queryParam start_date string optional Fecha inicio (Y-m-d). Default: primer día del mes actual.
     * @queryParam end_date string optional Fecha fin (Y-m-d). Default: último día del mes actual.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
            $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
            $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

            $data = [
                'filters' => [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'totals_pen' => $this->invoiceRepository->getTotalsByPeriod($companyId, $startDate, $endDate, 'PEN', $branchId),
                'totals_usd' => $this->invoiceRepository->getTotalsByPeriod($companyId, $startDate, $endDate, 'USD', $branchId),
                'top_clients' => $this->invoiceRepository->getTopClientsByRevenue($companyId, 10, $startDate, $endDate, $branchId),
                'pending_documents' => $this->invoiceRepository->getPendingSunat($companyId, 10, $branchId),
                'expiring_invoices' => $this->invoiceRepository->getExpiringSoon($companyId, 7, $branchId),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Estadísticas obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly summary
     *
     * @queryParam company_id int optional ID de la empresa.
     * @queryParam branch_id int optional ID de la sucursal.
     * @queryParam year int optional Año. Default: año actual.
     * @queryParam month int optional Mes (1-12). Default: mes actual.
     */
    public function monthlySummary(Request $request): JsonResponse
    {
        try {
            $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
            $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
            $year = $request->input('year', now()->year);
            $month = $request->input('month', now()->month);

            $summary = $this->invoiceRepository->getMonthlySummary($companyId, $year, $month, $branchId);

            return response()->json([
                'success' => true,
                'data' => [
                    'filters' => [
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'year' => (int) $year,
                        'month' => (int) $month
                    ],
                    'summary' => $summary
                ],
                'message' => 'Resumen mensual obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen mensual',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get client statistics
     *
     * @queryParam company_id int optional ID de la empresa.
     * @queryParam branch_id int optional ID de la sucursal.
     * @queryParam client_id int optional ID del cliente específico.
     */
    public function clientStatistics(Request $request): JsonResponse
    {
        try {
            $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
            $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
            $clientId = $request->input('client_id') ? (int) $request->input('client_id') : null;

            if ($clientId) {
                $data = $this->invoiceRepository->getByClient($clientId, 20);
            } else {
                $data = $this->clientRepository->getWithStatistics($companyId, $branchId);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'filters' => [
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'client_id' => $clientId
                    ],
                    'clients' => $data
                ],
                'message' => 'Estadísticas de clientes obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get documents requiring resend
     *
     * @queryParam company_id int optional ID de la empresa.
     * @queryParam branch_id int optional ID de la sucursal.
     * @queryParam limit int optional Límite de resultados. Default: 50.
     */
    public function requiresResend(Request $request): JsonResponse
    {
        try {
            $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
            $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
            $limit = $request->input('limit', 50);

            $documents = $this->invoiceRepository->getRequiringResend($companyId, $limit, $branchId);

            return response()->json([
                'success' => true,
                'data' => [
                    'filters' => [
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'limit' => (int) $limit
                    ],
                    'documents' => $documents,
                    'count' => $documents->count()
                ],
                'message' => 'Documentos pendientes de reenvío obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener documentos pendientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get companies with expired certificates
     */
    public function expiredCertificates(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days_before_expiration', 30);

            $companies = $this->companyRepository->getWithExpiredCertificates($days);

            return response()->json([
                'success' => true,
                'data' => $companies,
                'count' => $companies->count(),
                'message' => 'Empresas con certificados por vencer obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener certificados',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
