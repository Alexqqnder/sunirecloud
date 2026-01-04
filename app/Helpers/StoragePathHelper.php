<?php

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * Helper para generar rutas de almacenamiento organizadas por RUC y Sucursal
 *
 * Estructura:
 * empresas/{RUC}/
 * ├── certificado/
 * ├── logo/
 * └── sucursales/{CODIGO}/
 *     ├── facturas/
 *     ├── boletas/
 *     └── ...
 */
class StoragePathHelper
{
    /**
     * Obtiene el RUC de la empresa desde un documento
     *
     * @param mixed $document
     * @return string
     */
    public static function getCompanyRuc($document): string
    {
        // Intentar obtener RUC de diferentes formas
        if (isset($document->company->ruc)) {
            return $document->company->ruc;
        }

        if (isset($document->invoice->company->ruc)) {
            return $document->invoice->company->ruc;
        }

        if (isset($document->boleta->company->ruc)) {
            return $document->boleta->company->ruc;
        }

        throw new \Exception('No se pudo obtener el RUC de la empresa');
    }

    /**
     * Obtiene el código de sucursal desde un documento
     * Siempre retorna 4 dígitos con padding de ceros
     *
     * @param mixed $document
     * @return string
     */
    public static function getBranchCode($document): string
    {
        $codigo = null;

        // Intentar obtener código de diferentes formas
        if (isset($document->branch->codigo)) {
            $codigo = $document->branch->codigo;
        } elseif (isset($document->invoice->branch->codigo)) {
            $codigo = $document->invoice->branch->codigo;
        } elseif (isset($document->boleta->branch->codigo)) {
            $codigo = $document->boleta->branch->codigo;
        }

        // Si no hay código, usar 0000 por defecto
        if ($codigo === null) {
            $codigo = '0000';
        }

        // Asegurar 4 dígitos
        return str_pad($codigo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Genera ruta base de empresa
     *
     * @param string $ruc
     * @return string
     */
    public static function companyBasePath(string $ruc): string
    {
        return "empresas/{$ruc}";
    }

    /**
     * Genera ruta para certificados
     * empresas/{RUC}/certificado/
     *
     * @param string $ruc
     * @return string
     */
    public static function certificatePath(string $ruc): string
    {
        return self::companyBasePath($ruc) . "/certificado";
    }

    /**
     * Genera ruta para logos
     * empresas/{RUC}/logo/
     *
     * @param string $ruc
     * @return string
     */
    public static function logoPath(string $ruc): string
    {
        return self::companyBasePath($ruc) . "/logo";
    }

    /**
     * Genera ruta base de sucursal
     * empresas/{RUC}/sucursales/{CODIGO}/
     *
     * @param string $ruc
     * @param string $branchCode
     * @return string
     */
    public static function branchBasePath(string $ruc, string $branchCode): string
    {
        return self::companyBasePath($ruc) . "/sucursales/{$branchCode}";
    }

    /**
     * Genera ruta completa para documentos de sucursal
     * empresas/{RUC}/sucursales/{CODIGO}/{TIPO_DOC}/{TIPO_ARCHIVO}/{FECHA}/
     *
     * Ejemplos:
     * - empresas/20161515649/sucursales/0001/facturas/xml/05122025/
     * - empresas/20161515649/sucursales/0001/boletas/pdf/05122025/
     *
     * @param string $ruc RUC de la empresa
     * @param string $branchCode Código de sucursal (ej: 0001)
     * @param string $docType Tipo de documento (facturas, boletas, etc)
     * @param string $fileType Tipo de archivo (xml, pdf, cdr)
     * @param string $date Fecha en formato dmYYYY (05122025)
     * @return string
     */
    public static function documentPath(
        string $ruc,
        string $branchCode,
        string $docType,
        string $fileType,
        string $date
    ): string {
        return self::branchBasePath($ruc, $branchCode) . "/{$docType}/{$fileType}/{$date}";
    }

    /**
     * Genera nombre de archivo para documentos
     * {RUC}-{SERIE}-{CORRELATIVO}.{extension}
     *
     * Ejemplos:
     * - 20161515649-F001-00000123.xml
     * - 20161515649-B001-00000456.pdf
     *
     * @param string $ruc
     * @param string $numeroCompleto Serie-Correlativo (ej: F001-00000123)
     * @param string|null $format Para PDFs: a4, a5, 80mm, 50mm
     * @return string
     */
    public static function generateDocumentFilename(
        string $ruc,
        string $numeroCompleto,
        ?string $format = null
    ): string {
        $filename = "{$ruc}-{$numeroCompleto}";

        if ($format) {
            $filename .= "_{$format}";
        }

        return $filename;
    }

    /**
     * Genera nombre de archivo para CDR (Constancia de Recepción)
     * R-{RUC}-{SERIE}-{CORRELATIVO}.zip
     *
     * @param string $ruc
     * @param string $numeroCompleto
     * @return string
     */
    public static function generateCdrFilename(string $ruc, string $numeroCompleto): string
    {
        return "R-{$ruc}-{$numeroCompleto}";
    }

    /**
     * Obtiene el tipo de documento desde un modelo
     *
     * @param mixed $document
     * @return string
     */
    public static function getDocumentType($document): string
    {
        $class = class_basename($document);

        $types = [
            'Invoice' => 'facturas',
            'Boleta' => 'boletas',
            'NotaCredito' => 'notas-credito',
            'NotaDebito' => 'notas-debito',
            'GuiaRemision' => 'guias-remision',
            'ResumenDiario' => 'resumenes-diarios',
            'ComunicacionBaja' => 'comunicaciones-baja',
            'NotaVenta' => 'notas-venta',
        ];

        return $types[$class] ?? 'otros-comprobantes';
    }

    /**
     * Obtiene número completo del documento
     *
     * @param mixed $document
     * @return string
     */
    public static function getNumeroCompleto($document): string
    {
        if (isset($document->numero_completo)) {
            return $document->numero_completo;
        }

        if (isset($document->serie) && isset($document->correlativo)) {
            return $document->serie . '-' . str_pad($document->correlativo, 8, '0', STR_PAD_LEFT);
        }

        throw new \Exception('No se pudo obtener el número completo del documento');
    }

    /**
     * Genera ruta completa para un documento (conveniencia)
     *
     * @param mixed $document Modelo del documento
     * @param string $fileType xml, pdf, cdr
     * @param string $date Fecha en formato dmYYYY
     * @param string|null $format Para PDFs
     * @return array ['path' => ruta, 'filename' => nombre]
     */
    public static function generateFullPath(
        $document,
        string $fileType,
        string $date,
        ?string $format = null
    ): array {
        $ruc = self::getCompanyRuc($document);
        $branchCode = self::getBranchCode($document);
        $docType = self::getDocumentType($document);
        $numeroCompleto = self::getNumeroCompleto($document);

        // Generar ruta
        $path = self::documentPath($ruc, $branchCode, $docType, $fileType, $date);

        // Generar nombre de archivo
        if ($fileType === 'cdr') {
            $filename = self::generateCdrFilename($ruc, $numeroCompleto) . '.zip';
        } else {
            $filename = self::generateDocumentFilename($ruc, $numeroCompleto, $format);

            // Agregar extensión
            $extensions = [
                'xml' => '.xml',
                'pdf' => '.pdf',
            ];

            $filename .= $extensions[$fileType] ?? '';
        }

        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => "{$path}/{$filename}",
        ];
    }
}
