<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\StoreCompanyCompleteRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyPdfInfoRequest;
use App\Models\Company;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\StoragePathHelper;
use Exception;

class CompanyController extends Controller
{
    protected CertificateService $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Listar todas las empresas
     */
    public function index(): JsonResponse
    {
        try {
            $companies = Company::active()
                ->with(['branches'])
                ->select([
                    'id', 'ruc', 'razon_social', 'nombre_comercial', 
                    'direccion', 'distrito', 'provincia', 'departamento',
                    'email', 'telefono', 'modo_produccion', 'activo',
                    'created_at', 'updated_at'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $companies,
                'meta' => [
                    'total' => $companies->count(),
                    'active_count' => $companies->where('activo', true)->count(),
                    'production_count' => $companies->where('modo_produccion', true)->count()
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al listar empresas", ['error' => $e->getMessage()]);

            return $this->errorResponse('Error al obtener empresas', $e);
        }
    }

    /**
     * Crear nueva empresa (versión básica)
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        

        try {
            $validatedData = $this->processRequestData($request);
            $company = Company::create($validatedData);

            // Recargar con todas las relaciones
            $company = $company->fresh()->load('configurations');

            return response()->json([
                'success' => true,
                'message' => 'Empresa creada exitosamente',
                'data' => $this->formatCompanyResponse($company)
            ], 201);

        } catch (Exception $e) {
            return $this->errorResponse('Error al crear empresa', $e);
        }
    }

    /**
     * Crear nueva empresa con todos los datos (versión completa)
     */
    public function storeComplete(StoreCompanyCompleteRequest $request): JsonResponse
    {
        // Debug: Ver qué datos llegan
        Log::info("Datos recibidos en storeComplete:", [
            'all_data' => $request->all(),
            'cuentas_bancarias' => $request->input('cuentas_bancarias'),
            'billeteras_digitales' => $request->input('billeteras_digitales'),
            'has_files' => [
                'certificado' => $request->hasFile('certificado_pem'),
                'logo' => $request->hasFile('logo_path')
            ]
        ]);

        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            // Procesar booleanos
            $validatedData['modo_produccion'] = $this->processBoolean($validatedData['modo_produccion'] ?? false);
            $validatedData['activo'] = $this->processBoolean($validatedData['activo'] ?? true);
            $validatedData['mostrar_cuentas_en_pdf'] = $this->processBoolean($validatedData['mostrar_cuentas_en_pdf'] ?? true);
            $validatedData['mostrar_billeteras_en_pdf'] = $this->processBoolean($validatedData['mostrar_billeteras_en_pdf'] ?? true);
            $validatedData['mostrar_redes_sociales_en_pdf'] = $this->processBoolean($validatedData['mostrar_redes_sociales_en_pdf'] ?? false);
            $validatedData['mostrar_contactos_adicionales_en_pdf'] = $this->processBoolean($validatedData['mostrar_contactos_adicionales_en_pdf'] ?? true);

            // Procesar cuentas bancarias - activar por defecto
            if (isset($validatedData['cuentas_bancarias'])) {
                $validatedData['cuentas_bancarias'] = array_map(function($cuenta) {
                    $cuenta['activo'] = $cuenta['activo'] ?? true;
                    return $cuenta;
                }, $validatedData['cuentas_bancarias']);
            }

            // Procesar billeteras digitales - activar por defecto
            if (isset($validatedData['billeteras_digitales'])) {
                $validatedData['billeteras_digitales'] = array_map(function($billetera) {
                    $billetera['activo'] = $billetera['activo'] ?? true;
                    return $billetera;
                }, $validatedData['billeteras_digitales']);
            }

            // Crear empresa (los archivos se suben después con updatePdfInfo o update)
            $company = Company::create($validatedData);

            DB::commit();

            Log::info("Empresa completa creada exitosamente", [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'razon_social' => $company->razon_social,
                'has_cuentas' => !empty($company->cuentas_bancarias),
                'has_billeteras' => !empty($company->billeteras_digitales),
            ]);

            // Recargar empresa con todos los datos
            $company = $company->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Empresa creada exitosamente con toda la información',
                'data' => $this->formatCompanyResponse($company),
                'meta' => [
                    'cuentas_bancarias_count' => count($company->cuentas_bancarias ?? []),
                    'billeteras_digitales_count' => count($company->billeteras_digitales ?? []),
                    'modo_produccion' => $company->modo_produccion ? 'produccion' : 'beta',
                    'logo_uploaded' => !empty($company->logo_path),
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error("Error al crear empresa completa", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Error al crear empresa completa', $e);
        }
    }

    /**
     * Obtener empresa específica
     */
    public function show(Company $company): JsonResponse
    {
        try {
            $company->load([
                'branches',
                'configurations' => function($query) {
                    $query->active()->orderBy('config_type')->orderBy('environment');
                }
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->formatCompanyResponse($company),
                'stats' => $this->getCompanyStats($company)
            ]);

        } catch (Exception $e) {
            Log::error("Error al obtener empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al obtener empresa', $e);
        }
    }

    /**
     * Actualizar empresa
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        try {
            // Guardar RUC antiguo para detectar cambios
            $oldRuc = $company->ruc;

            $validatedData = $this->processRequestData($request, $company);

            // Detectar si el RUC cambió
            $newRuc = $validatedData['ruc'] ?? $oldRuc;
            $rucChanged = $oldRuc !== $newRuc;

            // Si el RUC cambió, actualizar las rutas de archivos en los datos validados
            if ($rucChanged) {
                $validatedData = $this->updateFilePathsForNewRuc($validatedData, $company, $oldRuc, $newRuc);
            }

            $company->update($validatedData);

            Log::info("Empresa actualizada exitosamente", [
                'company_id' => $company->id,
                'old_ruc' => $oldRuc,
                'new_ruc' => $company->ruc,
                'ruc_changed' => $rucChanged,
                'changes' => $company->getChanges()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Empresa actualizada exitosamente',
                'data' => $this->formatCompanyResponse($company->fresh()->load('configurations'))
            ]);

        } catch (Exception $e) {
            Log::error("Error al actualizar empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al actualizar empresa', $e);
        }
    }

    /**
     * Eliminar empresa (soft delete)
     */
    public function destroy(Company $company): JsonResponse
    {
        try {
            if ($this->hasAssociatedDocuments($company)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la empresa porque tiene documentos asociados. Considere desactivarla en su lugar.'
                ], 400);
            }

            $company->update(['activo' => false]);

            Log::warning("Empresa desactivada", [
                'company_id' => $company->id,
                'ruc' => $company->ruc
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Empresa desactivada exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error("Error al desactivar empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al desactivar empresa', $e);
        }
    }

    /**
     * Activar empresa
     */
    public function activate(Company $company): JsonResponse
    {
        try {
            $company->update(['activo' => true]);

            Log::info("Empresa activada", [
                'company_id' => $company->id,
                'ruc' => $company->ruc
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Empresa activada exitosamente',
                'data' => $company
            ]);

        } catch (Exception $e) {
            Log::error("Error al activar empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al activar empresa', $e);
        }
    }

    /**
     * Cambiar modo de producción
     */
    public function toggleProductionMode(Request $request, Company $company): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'modo_produccion' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldMode = $company->modo_produccion;
            $newMode = $request->modo_produccion;

            $company->update(['modo_produccion' => $newMode]);

            Log::info("Modo de producción cambiado", [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'old_mode' => $this->getModeName($oldMode),
                'new_mode' => $this->getModeName($newMode)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Modo de producción actualizado exitosamente',
                'data' => [
                    'company_id' => $company->id,
                    'modo_anterior' => $this->getModeName($oldMode),
                    'modo_actual' => $this->getModeName($newMode)
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al cambiar modo de producción", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al cambiar modo de producción', $e);
        }
    }

    /**
     * Procesar datos de la request
     */
    private function processRequestData(Request $request, ?Company $company = null): array
    {
        $validatedData = $request->validated();

        // Procesar booleanos
        $validatedData['modo_produccion'] = $this->processBoolean($validatedData['modo_produccion'] ?? false);
        $validatedData['activo'] = $this->processBoolean($validatedData['activo'] ?? true);

        // Determinar RUC a usar
        $oldRuc = $company ? $company->ruc : null;
        $newRuc = $validatedData['ruc'] ?? $oldRuc;

        // Detectar si el RUC cambió
        $rucChanged = $oldRuc && $newRuc && $oldRuc !== $newRuc;

        // Si el RUC cambió, migrar archivos al nuevo directorio
        if ($rucChanged) {
            $this->migrateCompanyFiles($oldRuc, $newRuc);
        }

        // Procesar archivos SOLO si tenemos RUC
        if ($newRuc) {
            if ($request->hasFile('certificado_pem')) {
                $certificateFile = $request->file('certificado_pem');
                $password = $validatedData['certificado_password'] ?? '';

                Log::info('Procesando certificado en processRequestData', [
                    'ruc' => $newRuc,
                    'original_name' => $certificateFile->getClientOriginalName(),
                    'extension' => $certificateFile->getClientOriginalExtension()
                ]);

                // Usar el servicio de certificados para procesar (convierte PFX a PEM si es necesario)
                $result = $this->certificateService->processCertificate($certificateFile, $password, $newRuc);

                if ($result['success']) {
                    $validatedData['certificado_pem'] = $result['pem_path'];

                    Log::info('Certificado procesado exitosamente', [
                        'ruc' => $newRuc,
                        'pem_path' => $result['pem_path'],
                        'certificate_info' => $result['certificate_info'] ?? null
                    ]);
                } else {
                    Log::error('Error al procesar certificado', [
                        'ruc' => $newRuc,
                        'error' => $result['message']
                    ]);
                    throw new Exception($result['message']);
                }
            }

            if ($request->hasFile('logo_path')) {
                $fileName = 'logo.' . $request->file('logo_path')->getClientOriginalExtension();
                // Usar nueva estructura: empresas/{RUC}/logo/
                $logoDirectory = StoragePathHelper::logoPath($newRuc);

                Log::info('Guardando logo en processRequestData', [
                    'ruc' => $newRuc,
                    'directory' => $logoDirectory,
                    'filename' => $fileName,
                    'full_path' => $logoDirectory . '/' . $fileName
                ]);

                $validatedData['logo_path'] = $this->storeFile($request->file('logo_path'), $logoDirectory, $fileName);
            }
        }

        return $validatedData;
    }

    /**
     * Actualizar rutas de archivos cuando cambia el RUC
     */
    private function updateFilePathsForNewRuc(array $validatedData, Company $company, string $oldRuc, string $newRuc): array
    {
        // Actualizar ruta del logo si existe
        if ($company->logo_path && str_contains($company->logo_path, $oldRuc)) {
            $validatedData['logo_path'] = str_replace(
                "empresas/{$oldRuc}/",
                "empresas/{$newRuc}/",
                $company->logo_path
            );

            Log::info('Ruta de logo actualizada', [
                'old_path' => $company->logo_path,
                'new_path' => $validatedData['logo_path']
            ]);
        }

        // Actualizar ruta del certificado si existe
        if ($company->certificado_pem && str_contains($company->certificado_pem, $oldRuc)) {
            $validatedData['certificado_pem'] = str_replace(
                "empresas/{$oldRuc}/",
                "empresas/{$newRuc}/",
                $company->certificado_pem
            );

            Log::info('Ruta de certificado actualizada', [
                'old_path' => $company->certificado_pem,
                'new_path' => $validatedData['certificado_pem']
            ]);
        }

        return $validatedData;
    }

    /**
     * Migrar archivos de empresa cuando cambia el RUC
     */
    private function migrateCompanyFiles(string $oldRuc, string $newRuc): void
    {
        try {
            $oldBasePath = StoragePathHelper::companyBasePath($oldRuc);
            $newBasePath = StoragePathHelper::companyBasePath($newRuc);

            // Obtener el disco de storage público
            $disk = Storage::disk('public');

            // Verificar si existe el directorio antiguo
            if (!$disk->exists($oldBasePath)) {
                Log::info('No existe directorio antiguo para migrar', [
                    'old_ruc' => $oldRuc,
                    'old_path' => $oldBasePath
                ]);
                return;
            }

            Log::info('Iniciando migración de archivos de empresa', [
                'old_ruc' => $oldRuc,
                'new_ruc' => $newRuc,
                'old_path' => $oldBasePath,
                'new_path' => $newBasePath
            ]);

            // Crear directorio nuevo si no existe
            if (!$disk->exists($newBasePath)) {
                $disk->makeDirectory($newBasePath);
            }

            // Obtener todos los archivos y subdirectorios del directorio antiguo
            $files = $disk->allFiles($oldBasePath);
            $directories = $disk->allDirectories($oldBasePath);

            // Mover archivos
            foreach ($files as $file) {
                // Calcular la ruta relativa desde el directorio base de la empresa
                $relativePath = str_replace($oldBasePath . '/', '', $file);
                $newPath = $newBasePath . '/' . $relativePath;

                // Crear directorio destino si no existe
                $newDir = dirname($newPath);
                if (!$disk->exists($newDir)) {
                    $disk->makeDirectory($newDir, 0755, true);
                }

                // Mover archivo
                $disk->move($file, $newPath);

                Log::info('Archivo migrado', [
                    'from' => $file,
                    'to' => $newPath
                ]);
            }

            // Eliminar el directorio antiguo (recursivamente)
            $disk->deleteDirectory($oldBasePath);

            Log::info('Migración de archivos completada', [
                'old_ruc' => $oldRuc,
                'new_ruc' => $newRuc,
                'files_migrated' => count($files)
            ]);

        } catch (Exception $e) {
            Log::error('Error al migrar archivos de empresa', [
                'old_ruc' => $oldRuc,
                'new_ruc' => $newRuc,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // No lanzar excepción para no bloquear la actualización
            // Los archivos pueden migrarse manualmente si es necesario
        }
    }

    /**
     * Procesar valor booleano
     */
    private function processBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Almacenar archivo
     */
    private function storeFile($file, string $directory, string $fileName): string
    {
        return $file->storeAs($directory, $fileName, 'public');
    }

    /**
     * Verificar documentos asociados
     */
    private function hasAssociatedDocuments(Company $company): bool
    {
        return $company->invoices()->exists() ||
               $company->boletas()->exists() ||
               $company->dispatchGuides()->exists();
    }

    /**
     * Obtener estadísticas de la empresa
     */
    private function getCompanyStats(Company $company): array
    {
        return [
            'branches_count' => $company->branches()->count(),
            'configurations_count' => $company->configurations()->active()->count(),
            'has_gre_credentials' => $company->hasGreCredentials(),
            'environment_mode' => $this->getModeName($company->modo_produccion)
        ];
    }

    /**
     * Obtener nombre del modo
     */
    private function getModeName(bool $mode): string
    {
        return $mode ? 'produccion' : 'beta';
    }

    /**
     * Obtener información PDF de la empresa
     */
    public function getPdfInfo(Company $company): JsonResponse
    {
        try {
            $pdfInfo = [
                'contactos' => [
                    'telefono' => $company->telefono,
                    'telefono_2' => $company->telefono_2,
                    'telefono_3' => $company->telefono_3,
                    'whatsapp' => $company->whatsapp,
                    'email' => $company->email,
                    'email_ventas' => $company->email_ventas,
                    'email_soporte' => $company->email_soporte,
                ],
                'redes_sociales' => [
                    'web' => $company->web,
                    'facebook' => $company->facebook,
                    'instagram' => $company->instagram,
                    'twitter' => $company->twitter,
                    'linkedin' => $company->linkedin,
                    'tiktok' => $company->tiktok,
                ],
                'cuentas_bancarias' => $company->cuentas_bancarias ?? [],
                'billeteras_digitales' => $company->billeteras_digitales ?? [],
                'mensajes' => [
                    'mensaje_pdf' => $company->mensaje_pdf,
                    'terminos_condiciones_pdf' => $company->terminos_condiciones_pdf,
                    'politica_garantia' => $company->politica_garantia,
                ],
                'configuracion' => [
                    'mostrar_cuentas_en_pdf' => $company->mostrar_cuentas_en_pdf,
                    'mostrar_billeteras_en_pdf' => $company->mostrar_billeteras_en_pdf,
                    'mostrar_redes_sociales_en_pdf' => $company->mostrar_redes_sociales_en_pdf,
                    'mostrar_contactos_adicionales_en_pdf' => $company->mostrar_contactos_adicionales_en_pdf,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $pdfInfo
            ]);

        } catch (Exception $e) {
            Log::error("Error al obtener información PDF de empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al obtener información PDF', $e);
        }
    }

    /**
     * Actualizar información PDF de la empresa
     */
    public function updatePdfInfo(UpdateCompanyPdfInfoRequest $request, Company $company): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // Procesar booleanos
            if (isset($validatedData['mostrar_cuentas_en_pdf'])) {
                $validatedData['mostrar_cuentas_en_pdf'] = $this->processBoolean($validatedData['mostrar_cuentas_en_pdf']);
            }
            if (isset($validatedData['mostrar_billeteras_en_pdf'])) {
                $validatedData['mostrar_billeteras_en_pdf'] = $this->processBoolean($validatedData['mostrar_billeteras_en_pdf']);
            }
            if (isset($validatedData['mostrar_redes_sociales_en_pdf'])) {
                $validatedData['mostrar_redes_sociales_en_pdf'] = $this->processBoolean($validatedData['mostrar_redes_sociales_en_pdf']);
            }
            if (isset($validatedData['mostrar_contactos_adicionales_en_pdf'])) {
                $validatedData['mostrar_contactos_adicionales_en_pdf'] = $this->processBoolean($validatedData['mostrar_contactos_adicionales_en_pdf']);
            }

            // Activar por defecto las cuentas y billeteras si se están agregando
            if (isset($validatedData['cuentas_bancarias'])) {
                $validatedData['cuentas_bancarias'] = array_map(function($cuenta) {
                    $cuenta['activo'] = $cuenta['activo'] ?? true;
                    return $cuenta;
                }, $validatedData['cuentas_bancarias']);
            }

            if (isset($validatedData['billeteras_digitales'])) {
                $validatedData['billeteras_digitales'] = array_map(function($billetera) {
                    $billetera['activo'] = $billetera['activo'] ?? true;
                    return $billetera;
                }, $validatedData['billeteras_digitales']);
            }

            $company->update($validatedData);

            Log::info("Información PDF de empresa actualizada", [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'changes' => array_keys($validatedData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Información PDF actualizada exitosamente',
                'data' => $company->fresh()
            ]);

        } catch (Exception $e) {
            Log::error("Error al actualizar información PDF de empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al actualizar información PDF', $e);
        }
    }

    /**
     * Subir archivos de empresa (logo y certificado)
     */
    public function uploadFiles(Request $request, Company $company): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'logo_path' => 'nullable|file|mimes:png,jpeg,jpg|max:2048',
                'certificado_pem' => 'nullable|file|max:2048',
                'certificado_password' => 'required_if:certificado_pem,!=,null|nullable|string|max:100',
            ], [
                'logo_path.mimes' => 'El logo debe estar en formato PNG o JPG',
                'logo_path.max' => 'El logo no debe exceder 2MB',
                'certificado_pem.file' => 'El certificado debe ser un archivo válido (.pfx, .p12, .pem, .crt, .cer)',
                'certificado_pem.max' => 'El certificado no debe exceder 2MB',
                'certificado_password.required_if' => 'La contraseña del certificado es requerida cuando se sube un certificado',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updated = false;
            $filesUploaded = [];
            $certificateInfo = null;

            // Validar que la empresa tenga RUC
            if (empty($company->ruc)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La empresa debe tener un RUC registrado antes de subir archivos'
                ], 400);
            }

            Log::info("Subiendo archivos para empresa", [
                'company_id' => $company->id,
                'ruc' => $company->ruc
            ]);

            // Procesar logo
            if ($request->hasFile('logo_path')) {
                // Usar nueva estructura: empresas/{RUC}/logo/
                $logoDirectory = StoragePathHelper::logoPath($company->ruc);
                $extension = $request->file('logo_path')->getClientOriginalExtension();
                $fileName = "logo.{$extension}";

                Log::info("Guardando logo", [
                    'directory' => $logoDirectory,
                    'filename' => $fileName
                ]);

                $logoPath = $this->storeFile($request->file('logo_path'), $logoDirectory, $fileName);

                $company->update(['logo_path' => $logoPath]);
                $filesUploaded['logo'] = $logoPath;
                $updated = true;
            }

            // Procesar certificado (soporta PFX y PEM)
            if ($request->hasFile('certificado_pem')) {
                $certificateFile = $request->file('certificado_pem');
                $password = $request->input('certificado_password', '');

                Log::info("Procesando certificado", [
                    'original_name' => $certificateFile->getClientOriginalName(),
                    'extension' => $certificateFile->getClientOriginalExtension()
                ]);

                // Usar el servicio de certificados para procesar (convierte PFX a PEM si es necesario)
                $result = $this->certificateService->processCertificate($certificateFile, $password, $company->ruc);

                if ($result['success']) {
                    $company->update([
                        'certificado_pem' => $result['pem_path'],
                        'certificado_password' => $password
                    ]);
                    $filesUploaded['certificado'] = $result['pem_path'];
                    $certificateInfo = $result['certificate_info'];
                    $updated = true;

                    Log::info("Certificado procesado exitosamente", [
                        'pem_path' => $result['pem_path'],
                        'certificate_info' => $certificateInfo
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al procesar certificado: ' . $result['message']
                    ], 400);
                }
            }

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se proporcionaron archivos para subir'
                ], 400);
            }

            Log::info("Archivos subidos para empresa", [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'files' => $filesUploaded
            ]);

            $responseData = [
                'company_id' => $company->id,
                'files_uploaded' => $filesUploaded
            ];

            // Agregar información del certificado si está disponible
            if ($certificateInfo) {
                $responseData['certificate_info'] = $certificateInfo;
            }

            return response()->json([
                'success' => true,
                'message' => 'Archivos subidos exitosamente',
                'data' => $responseData
            ]);

        } catch (Exception $e) {
            Log::error("Error al subir archivos de empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al subir archivos', $e);
        }
    }

    /**
     * Formatear respuesta completa de empresa
     */
    private function formatCompanyResponse(Company $company): array
    {
        return [
            // Datos básicos
            'id' => $company->id,
            'ruc' => $company->ruc,
            'razon_social' => $company->razon_social,
            'nombre_comercial' => $company->nombre_comercial,

            // Ubicación
            'direccion' => $company->direccion,
            'ubigeo' => $company->ubigeo,
            'distrito' => $company->distrito,
            'provincia' => $company->provincia,
            'departamento' => $company->departamento,

            // Contacto principal
            'telefono' => $company->telefono,
            'email' => $company->email,
            'web' => $company->web,

            // Contactos adicionales
            'telefono_2' => $company->telefono_2,
            'telefono_3' => $company->telefono_3,
            'whatsapp' => $company->whatsapp,
            'email_ventas' => $company->email_ventas,
            'email_soporte' => $company->email_soporte,

            // Redes sociales
            'facebook' => $company->facebook,
            'instagram' => $company->instagram,
            'twitter' => $company->twitter,
            'linkedin' => $company->linkedin,
            'tiktok' => $company->tiktok,

            // Cuentas y billeteras
            'cuentas_bancarias' => $company->cuentas_bancarias ?? [],
            'billeteras_digitales' => $company->billeteras_digitales ?? [],

            // Información PDF
            'mensaje_pdf' => $company->mensaje_pdf,
            'terminos_condiciones_pdf' => $company->terminos_condiciones_pdf,
            'politica_garantia' => $company->politica_garantia,

            // Configuración PDF
            'mostrar_cuentas_en_pdf' => $company->mostrar_cuentas_en_pdf,
            'mostrar_billeteras_en_pdf' => $company->mostrar_billeteras_en_pdf,
            'mostrar_redes_sociales_en_pdf' => $company->mostrar_redes_sociales_en_pdf,
            'mostrar_contactos_adicionales_en_pdf' => $company->mostrar_contactos_adicionales_en_pdf,

            // Credenciales SUNAT (solo públicas)
            'usuario_sol' => $company->usuario_sol,

            // Credenciales GRE (solo públicas)
            'gre_client_id_beta' => $company->gre_client_id_beta,
            'gre_client_id_produccion' => $company->gre_client_id_produccion,
            'gre_ruc_proveedor' => $company->gre_ruc_proveedor,
            'gre_usuario_sol' => $company->gre_usuario_sol,

            // Endpoints
            'endpoint_beta' => $company->endpoint_beta,
            'endpoint_produccion' => $company->endpoint_produccion,

            // Configuración general
            'modo_produccion' => $company->modo_produccion,
            'logo_path' => $company->logo_path,
            'activo' => $company->activo,

            // Timestamps
            'created_at' => $company->created_at,
            'updated_at' => $company->updated_at,

            // Relaciones (si están cargadas)
            'configurations' => $company->relationLoaded('configurations') ? $company->configurations : null,
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
}