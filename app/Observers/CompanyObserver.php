<?php

namespace App\Observers;

use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        Log::info('Nueva empresa registrada', [
            'company_id' => $company->id,
            'ruc' => $company->ruc,
            'razon_social' => $company->razon_social,
            'created_by' => auth()->user()->email ?? 'system'
        ]);
    }

    /**
     * Handle the Company "updating" event.
     */
    public function updating(Company $company): void
    {
        // Manejar cambio de RUC - mover archivos si es necesario
        if ($company->isDirty('ruc')) {
            $this->handleRucChange($company);
        }

        // Auditar cambios en credenciales SOL
        if ($company->isDirty('usuario_sol') || $company->isDirty('clave_sol')) {
            Log::channel('audit')->warning('Credenciales SOL modificadas', [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'usuario_sol_changed' => $company->isDirty('usuario_sol'),
                'clave_sol_changed' => $company->isDirty('clave_sol'),
                'modified_by' => auth()->user()->email ?? 'system',
                'ip' => request()->ip()
            ]);
        }

        // Auditar cambio de modo (beta/producción)
        if ($company->isDirty('modo_produccion')) {
            Log::channel('audit')->warning('Modo de operación cambiado', [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'old_mode' => $company->getOriginal('modo_produccion') ? 'PRODUCCIÓN' : 'BETA',
                'new_mode' => $company->modo_produccion ? 'PRODUCCIÓN' : 'BETA',
                'modified_by' => auth()->user()->email ?? 'system',
                'ip' => request()->ip()
            ]);
        }

        // Auditar cambio de estado activo
        if ($company->isDirty('activo')) {
            Log::channel('audit')->warning('Estado de empresa modificado', [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'old_status' => $company->getOriginal('activo') ? 'ACTIVA' : 'INACTIVA',
                'new_status' => $company->activo ? 'ACTIVA' : 'INACTIVA',
                'modified_by' => auth()->user()->email ?? 'system'
            ]);
        }
    }

    /**
     * Manejar cambio de RUC - mover archivos a nueva estructura
     */
    private function handleRucChange(Company $company): void
    {
        $originalRuc = $company->getOriginal('ruc');
        $newRuc = $company->ruc;

        Log::warning('Cambio de RUC detectado - moviendo archivos', [
            'company_id' => $company->id,
            'original_ruc' => $originalRuc,
            'new_ruc' => $newRuc,
            'razon_social' => $company->razon_social
        ]);

        try {
            $disk = Storage::disk('public');
            $oldBasePath = "empresas/{$originalRuc}";
            $newBasePath = "empresas/{$newRuc}";

            // Solo mover si la carpeta antigua existe
            if ($disk->exists($oldBasePath)) {
                // Crear nueva carpeta si no existe
                if (!$disk->exists($newBasePath)) {
                    $disk->makeDirectory($newBasePath);
                }

                // Mover certificado
                if (!empty($company->certificado_pem) && str_contains($company->certificado_pem, $originalRuc)) {
                    $this->moveCertificate($disk, $originalRuc, $newRuc, $company);
                }

                // Mover logo
                if (!empty($company->logo_path) && str_contains($company->logo_path, $originalRuc)) {
                    $this->moveLogo($disk, $originalRuc, $newRuc, $company);
                }

                Log::info('Archivos movidos exitosamente por cambio de RUC', [
                    'company_id' => $company->id,
                    'old_ruc' => $originalRuc,
                    'new_ruc' => $newRuc
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al mover archivos por cambio de RUC', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mover certificado a nueva ubicación por RUC
     */
    private function moveCertificate($disk, string $oldRuc, string $newRuc, Company $company): void
    {
        $oldCertPath = "empresas/{$oldRuc}/certificado";
        $newCertPath = "empresas/{$newRuc}/certificado";

        if ($disk->exists($oldCertPath)) {
            if (!$disk->exists($newCertPath)) {
                $disk->makeDirectory($newCertPath);
            }

            if ($disk->exists("{$oldCertPath}/certificado.pem")) {
                $disk->copy(
                    "{$oldCertPath}/certificado.pem",
                    "{$newCertPath}/certificado.pem"
                );

                // Actualizar ruta en el modelo
                $company->certificado_pem = "{$newCertPath}/certificado.pem";
            }
        }
    }

    /**
     * Mover logo a nueva ubicación por RUC
     */
    private function moveLogo($disk, string $oldRuc, string $newRuc, Company $company): void
    {
        $oldLogoPath = "empresas/{$oldRuc}/logo";
        $newLogoPath = "empresas/{$newRuc}/logo";

        if ($disk->exists($oldLogoPath)) {
            if (!$disk->exists($newLogoPath)) {
                $disk->makeDirectory($newLogoPath);
            }

            $logoFileName = basename($company->logo_path);

            if ($disk->exists("{$oldLogoPath}/{$logoFileName}")) {
                $disk->copy(
                    "{$oldLogoPath}/{$logoFileName}",
                    "{$newLogoPath}/{$logoFileName}"
                );

                // Actualizar ruta en el modelo
                $company->logo_path = "{$newLogoPath}/{$logoFileName}";
            }
        }
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        Log::channel('audit')->critical('Empresa eliminada', [
            'company_id' => $company->id,
            'ruc' => $company->ruc,
            'razon_social' => $company->razon_social,
            'deleted_by' => auth()->user()->email ?? 'system',
            'timestamp' => now()->toISOString()
        ]);
    }
}
