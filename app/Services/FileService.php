<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Helpers\StoragePathHelper;

class FileService
{
    public function saveXml($document, string $xmlContent): string
    {
        try {
            $this->ensureDirectoryExists($document, 'xml');
            $path = $this->generatePath($document, 'xml');

            // Validar que el contenido no esté vacío
            if (empty($xmlContent)) {
                throw new \Exception("El contenido del XML está vacío");
            }

            // Guardar archivo
            $saved = Storage::disk('public')->put($path, $xmlContent);

            if (!$saved) {
                throw new \Exception("No se pudo guardar el archivo XML en: {$path}");
            }

            // Verificar que el archivo se guardó correctamente
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception("El archivo XML no existe después de guardarlo: {$path}");
            }

            \Log::info("XML guardado correctamente", [
                'document_type' => class_basename($document),
                'document_id' => $document->id,
                'path' => $path,
                'size' => strlen($xmlContent)
            ]);

            return $path;
        } catch (\Exception $e) {
            \Log::error("Error al guardar XML", [
                'document_type' => class_basename($document),
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function saveCdr($document, string $cdrContent): string
    {
        try {
            $this->ensureDirectoryExists($document, 'zip');
            $path = $this->generatePath($document, 'zip');

            // Validar que el contenido no esté vacío
            if (empty($cdrContent)) {
                throw new \Exception("El contenido del CDR está vacío");
            }

            // Guardar archivo
            $saved = Storage::disk('public')->put($path, $cdrContent);

            if (!$saved) {
                throw new \Exception("No se pudo guardar el archivo CDR en: {$path}");
            }

            // Verificar que el archivo se guardó correctamente
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception("El archivo CDR no existe después de guardarlo: {$path}");
            }

            \Log::info("CDR guardado correctamente", [
                'document_type' => class_basename($document),
                'document_id' => $document->id,
                'path' => $path,
                'size' => strlen($cdrContent)
            ]);

            return $path;
        } catch (\Exception $e) {
            \Log::error("Error al guardar CDR", [
                'document_type' => class_basename($document),
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function savePdf($document, string $pdfContent, string $format = 'A4'): string
    {
        $this->ensureDirectoryExists($document, 'pdf');
        $path = $this->generatePath($document, 'pdf', $format);
        Storage::disk('public')->put($path, $pdfContent);
        return $path;
    }

    protected function generatePath($document, string $extension, string $format = 'A4'): string
    {
        $date = Carbon::parse($document->fecha_emision);
        $dateFolder = $date->format('dmY'); // Formato: 05122025

        // Obtener RUC y código de sucursal usando el helper
        try {
            $ruc = StoragePathHelper::getCompanyRuc($document);
            $branchCode = StoragePathHelper::getBranchCode($document);
            $numeroCompleto = StoragePathHelper::getNumeroCompleto($document);
        } catch (\Exception $e) {
            \Log::error("Error al obtener datos para ruta", [
                'document_type' => class_basename($document),
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        // Obtener tipo de comprobante
        $docType = StoragePathHelper::getDocumentType($document);

        // Determinar el tipo de archivo (xml, cdr o pdf)
        $fileType = $extension === 'zip' ? 'cdr' : $extension;

        // Generar ruta usando el helper
        // empresas/{RUC}/sucursales/{CODIGO}/{TIPO_DOC}/{TIPO_ARCHIVO}/{FECHA}/
        $directory = StoragePathHelper::documentPath($ruc, $branchCode, $docType, $fileType, $dateFolder);

        // Generar nombre de archivo
        if ($extension === 'zip') {
            // CDR: R-{RUC}-{NUMERO_COMPLETO}.zip
            $filename = StoragePathHelper::generateCdrFilename($ruc, $numeroCompleto) . '.zip';
        } else {
            // XML o PDF: {RUC}-{NUMERO_COMPLETO}[_FORMAT].{extension}
            $formatParam = ($extension === 'pdf' && $format !== 'A4') ? $format : null;
            $filename = StoragePathHelper::generateDocumentFilename($ruc, $numeroCompleto, $formatParam) . '.' . $extension;
        }

        return "{$directory}/{$filename}";
    }

    /**
     * Obtener el nombre del archivo del documento de forma segura
     */
    protected function getDocumentFileName($document): string
    {
        // Intentar obtener numero_completo (facturas, boletas, etc.)
        if (isset($document->numero_completo) && !empty($document->numero_completo)) {
            return $document->numero_completo;
        }

        // Intentar obtener identificador (voided documents, daily summaries)
        if (isset($document->identificador) && !empty($document->identificador)) {
            return $document->identificador;
        }

        // Si es un modelo con accessor numero_completo
        if (method_exists($document, 'getNumeroCompletoAttribute')) {
            $numeroCompleto = $document->numero_completo;
            if (!empty($numeroCompleto)) {
                return $numeroCompleto;
            }
        }

        // Fallback: generar nombre basado en tipo y ID
        $className = class_basename($document);
        $modelName = strtolower($className);

        return "{$modelName}_{$document->id}_" . date('YmdHis');
    }

    protected function getDocumentTypeName($document): string
    {
        // Determinar el nombre de la carpeta según el tipo de documento
        // Verificar si es un modelo Eloquent con el atributo tipo_documento
        if (isset($document->tipo_documento)) {
            return match($document->tipo_documento) {
                '01' => 'facturas',
                '03' => 'boletas',
                '07' => 'notas-credito',
                '08' => 'notas-debito',
                '09' => 'guias-remision',
                '20' => 'percepciones',
                '21' => 'retenciones',
                default => 'otros-comprobantes'
            };
        }
        
        // Fallback basado en el nombre de la clase del modelo
        $className = class_basename($document);
        return match($className) {
            'Invoice' => 'facturas',  // Corregido: Invoice en lugar de Factura
            'Boleta' => 'boletas',
            'CreditNote' => 'notas-credito',
            'DebitNote' => 'notas-debito', 
            'DispatchGuide' => 'guias-remision',
            'Percepcion' => 'percepciones',
            'Retencion' => 'retenciones',
            'DailySummary' => 'resumenes-diarios',
            default => 'otros-comprobantes'
        };
    }

    public function getXmlPath($document): ?string
    {
        if (!$document->xml_path) {
            return null;
        }
        
        return Storage::disk('public')->exists($document->xml_path) 
            ? Storage::disk('public')->path($document->xml_path)
            : null;
    }

    public function getCdrPath($document): ?string
    {
        if (!$document->cdr_path) {
            return null;
        }
        
        return Storage::disk('public')->exists($document->cdr_path)
            ? Storage::disk('public')->path($document->cdr_path)
            : null;
    }

    public function getPdfPath($document): ?string
    {
        if (!$document->pdf_path) {
            return null;
        }
        
        return Storage::disk('public')->exists($document->pdf_path)
            ? Storage::disk('public')->path($document->pdf_path)
            : null;
    }

    public function downloadXml($document)
    {
        if (!$document->xml_path || !Storage::disk('public')->exists($document->xml_path)) {
            return null;
        }
        
        return Storage::disk('public')->download(
            $document->xml_path,
            $document->numero_completo . '.xml'
        );
    }

    public function downloadCdr($document)
    {
        if (!$document->cdr_path || !Storage::disk('public')->exists($document->cdr_path)) {
            return null;
        }
        
        return Storage::disk('public')->download(
            $document->cdr_path,
            'R-' . $document->numero_completo . '.zip'
        );
    }

    public function downloadPdf($document)
    {
        if (!$document->pdf_path || !Storage::disk('public')->exists($document->pdf_path)) {
            return null;
        }

        return Storage::disk('public')->download(
            $document->pdf_path,
            $document->numero_completo . '.pdf'
        );
    }

    /**
     * Get PDF path with specific format
     */
    public function getPdfPathWithFormat($document, string $format = 'A4'): ?string
    {
        // Siempre generar la ruta esperada para el formato solicitado
        // No usar pdf_path directamente porque podría apuntar a cualquier formato
        return $this->generatePath($document, 'pdf', $format);
    }

    /**
     * Download PDF by specific path
     */
    public function downloadPdfByPath(string $pdfPath, string $fileName)
    {
        if (!Storage::disk('public')->exists($pdfPath)) {
            return null;
        }

        return Storage::disk('public')->download(
            $pdfPath,
            $fileName . '.pdf'
        );
    }

    public function createDirectoryStructure(): void
    {
        // Tipos de comprobantes
        $tiposComprobantes = [
            'facturas',
            'boletas', 
            'notas-credito',
            'notas-debito',
            'guias-remision',
            'percepciones',
            'retenciones',
            'resumenes-diarios',
            'otros-comprobantes'
        ];
        
        // Tipos de archivos
        $tiposArchivos = ['xml', 'cdr', 'pdf'];
        
        // Crear estructura de directorios base
        foreach ($tiposComprobantes as $tipoComprobante) {
            foreach ($tiposArchivos as $tipoArchivo) {
                $directory = "{$tipoComprobante}/{$tipoArchivo}";
                Storage::disk('public')->makeDirectory($directory);
            }
        }
    }

    public function ensureDirectoryExists($document, string $extension): void
    {
        $date = Carbon::parse($document->fecha_emision);
        $dateFolder = $date->format('dmY');

        // Obtener datos usando el helper
        try {
            $ruc = StoragePathHelper::getCompanyRuc($document);
            $branchCode = StoragePathHelper::getBranchCode($document);
            $docType = StoragePathHelper::getDocumentType($document);
            $fileType = $extension === 'zip' ? 'cdr' : $extension;

            // Generar ruta completa del directorio
            $directory = StoragePathHelper::documentPath($ruc, $branchCode, $docType, $fileType, $dateFolder);

            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);

                \Log::info("Directorio creado", [
                    'directory' => $directory,
                    'ruc' => $ruc,
                    'branch_code' => $branchCode
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Error al crear directorio", [
                'document_type' => class_basename($document),
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}