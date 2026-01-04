<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Helpers\StoragePathHelper;

class CertificateService
{
    /**
     * Convierte un archivo PFX a formato PEM
     *
     * @param UploadedFile $pfxFile Archivo PFX subido
     * @param string $password Contraseña del archivo PFX
     * @param string $ruc RUC de la empresa para determinar la ruta de almacenamiento
     * @return array{success: bool, pem_path: string|null, message: string}
     */
    public function convertPfxToPem(UploadedFile $pfxFile, string $password, string $ruc): array
    {
        try {
            // Validar que OpenSSL esté disponible
            if (!extension_loaded('openssl')) {
                throw new Exception('La extensión OpenSSL no está habilitada en PHP');
            }

            // Leer el contenido del archivo PFX
            $pfxContent = file_get_contents($pfxFile->getRealPath());

            if ($pfxContent === false) {
                throw new Exception('No se pudo leer el archivo PFX');
            }

            // Parsear el archivo PFX
            $certs = [];
            $parsed = openssl_pkcs12_read($pfxContent, $certs, $password);

            if (!$parsed) {
                $openSslError = openssl_error_string();
                Log::error('Error al parsear PFX', [
                    'ruc' => $ruc,
                    'openssl_error' => $openSslError
                ]);
                throw new Exception('No se pudo leer el archivo PFX. Verifique que la contraseña sea correcta. Error: ' . $openSslError);
            }

            // Verificar que se obtuvieron el certificado y la clave privada
            if (empty($certs['cert']) || empty($certs['pkey'])) {
                throw new Exception('El archivo PFX no contiene un certificado válido o clave privada');
            }

            // Construir el contenido PEM (clave privada + certificado)
            $pemContent = $certs['pkey'] . $certs['cert'];

            // Si hay certificados de cadena (CA), agregarlos también
            if (!empty($certs['extracerts']) && is_array($certs['extracerts'])) {
                foreach ($certs['extracerts'] as $extraCert) {
                    $pemContent .= $extraCert;
                }
            }

            // Definir la ruta de almacenamiento
            $certDirectory = StoragePathHelper::certificatePath($ruc);
            $pemFileName = 'certificado.pem';
            $pemFullPath = $certDirectory . '/' . $pemFileName;

            // Asegurar que el directorio existe
            Storage::disk('public')->makeDirectory($certDirectory);

            // Guardar el archivo PEM
            $saved = Storage::disk('public')->put($pemFullPath, $pemContent);

            if (!$saved) {
                throw new Exception('No se pudo guardar el archivo PEM');
            }

            Log::info('Certificado PFX convertido a PEM exitosamente', [
                'ruc' => $ruc,
                'pem_path' => $pemFullPath
            ]);

            return [
                'success' => true,
                'pem_path' => $pemFullPath,
                'message' => 'Certificado convertido exitosamente de PFX a PEM'
            ];

        } catch (Exception $e) {
            Log::error('Error al convertir PFX a PEM', [
                'ruc' => $ruc,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'pem_path' => null,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida si un archivo es un PFX válido
     *
     * @param UploadedFile $file Archivo a validar
     * @param string $password Contraseña del archivo
     * @return array{valid: bool, message: string}
     */
    public function validatePfxFile(UploadedFile $file, string $password): array
    {
        try {
            $pfxContent = file_get_contents($file->getRealPath());

            if ($pfxContent === false) {
                return [
                    'valid' => false,
                    'message' => 'No se pudo leer el archivo'
                ];
            }

            $certs = [];
            $parsed = openssl_pkcs12_read($pfxContent, $certs, $password);

            if (!$parsed) {
                return [
                    'valid' => false,
                    'message' => 'Contraseña incorrecta o archivo PFX inválido'
                ];
            }

            if (empty($certs['cert']) || empty($certs['pkey'])) {
                return [
                    'valid' => false,
                    'message' => 'El archivo PFX no contiene certificado o clave privada'
                ];
            }

            // Verificar fecha de expiración
            $certInfo = openssl_x509_parse($certs['cert']);
            if ($certInfo && isset($certInfo['validTo_time_t'])) {
                $expirationDate = $certInfo['validTo_time_t'];
                if ($expirationDate < time()) {
                    return [
                        'valid' => false,
                        'message' => 'El certificado ha expirado'
                    ];
                }
            }

            return [
                'valid' => true,
                'message' => 'Archivo PFX válido'
            ];

        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error al validar el archivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene información del certificado
     *
     * @param UploadedFile $file Archivo PFX
     * @param string $password Contraseña
     * @return array|null
     */
    public function getCertificateInfo(UploadedFile $file, string $password): ?array
    {
        try {
            $pfxContent = file_get_contents($file->getRealPath());
            $certs = [];

            if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
                return null;
            }

            $certInfo = openssl_x509_parse($certs['cert']);

            if (!$certInfo) {
                return null;
            }

            return [
                'subject' => $certInfo['subject'] ?? [],
                'issuer' => $certInfo['issuer'] ?? [],
                'valid_from' => isset($certInfo['validFrom_time_t'])
                    ? date('Y-m-d H:i:s', $certInfo['validFrom_time_t'])
                    : null,
                'valid_to' => isset($certInfo['validTo_time_t'])
                    ? date('Y-m-d H:i:s', $certInfo['validTo_time_t'])
                    : null,
                'serial_number' => $certInfo['serialNumber'] ?? null,
                'is_expired' => isset($certInfo['validTo_time_t'])
                    ? $certInfo['validTo_time_t'] < time()
                    : null,
                'days_until_expiration' => isset($certInfo['validTo_time_t'])
                    ? max(0, floor(($certInfo['validTo_time_t'] - time()) / 86400))
                    : null,
            ];

        } catch (Exception $e) {
            Log::error('Error al obtener información del certificado', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Determina si el archivo es PFX o PEM basado en la extensión
     *
     * @param UploadedFile $file
     * @return string 'pfx'|'pem'|'unknown'
     */
    public function detectCertificateType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $pfxExtensions = ['pfx', 'p12'];
        $pemExtensions = ['pem', 'crt', 'cer', 'txt'];

        if (in_array($extension, $pfxExtensions)) {
            return 'pfx';
        }

        if (in_array($extension, $pemExtensions)) {
            return 'pem';
        }

        // Intentar detectar por contenido
        $content = file_get_contents($file->getRealPath());

        // Los archivos PEM contienen "-----BEGIN"
        if (strpos($content, '-----BEGIN') !== false) {
            return 'pem';
        }

        return 'unknown';
    }

    /**
     * Procesa un archivo de certificado (PFX o PEM) y lo guarda como PEM
     *
     * @param UploadedFile $file Archivo de certificado
     * @param string $password Contraseña (requerida para PFX)
     * @param string $ruc RUC de la empresa
     * @return array{success: bool, pem_path: string|null, message: string, certificate_info: array|null}
     */
    public function processCertificate(UploadedFile $file, string $password, string $ruc): array
    {
        $certificateType = $this->detectCertificateType($file);

        Log::info('Procesando certificado', [
            'ruc' => $ruc,
            'type' => $certificateType,
            'original_name' => $file->getClientOriginalName()
        ]);

        if ($certificateType === 'pfx') {
            // Validar PFX primero
            $validation = $this->validatePfxFile($file, $password);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'pem_path' => null,
                    'message' => $validation['message'],
                    'certificate_info' => null
                ];
            }

            // Obtener información del certificado
            $certInfo = $this->getCertificateInfo($file, $password);

            // Convertir a PEM
            $result = $this->convertPfxToPem($file, $password, $ruc);
            $result['certificate_info'] = $certInfo;

            return $result;

        } elseif ($certificateType === 'pem') {
            // Guardar directamente el archivo PEM
            $certDirectory = StoragePathHelper::certificatePath($ruc);
            $pemFileName = 'certificado.pem';
            $pemFullPath = $certDirectory . '/' . $pemFileName;

            Storage::disk('public')->makeDirectory($certDirectory);

            $saved = Storage::disk('public')->put(
                $pemFullPath,
                file_get_contents($file->getRealPath())
            );

            if (!$saved) {
                return [
                    'success' => false,
                    'pem_path' => null,
                    'message' => 'No se pudo guardar el archivo PEM',
                    'certificate_info' => null
                ];
            }

            Log::info('Certificado PEM guardado directamente', [
                'ruc' => $ruc,
                'pem_path' => $pemFullPath
            ]);

            return [
                'success' => true,
                'pem_path' => $pemFullPath,
                'message' => 'Certificado PEM guardado exitosamente',
                'certificate_info' => null
            ];

        } else {
            return [
                'success' => false,
                'pem_path' => null,
                'message' => 'Tipo de certificado no reconocido. Use archivos .pfx, .p12, .pem, .crt o .cer',
                'certificate_info' => null
            ];
        }
    }
}
