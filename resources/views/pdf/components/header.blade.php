{{-- PDF Header Component --}}
{{-- Props: $company, $document, $tipo_documento_nombre, $fecha_emision, $format --}}

@php
    // Cargar logo desde la base de datos de la empresa
    $logoPath = null;
    if (!empty($company->logo_path)) {
        $fullPath = storage_path('app/public/' . $company->logo_path);
        if (file_exists($fullPath)) {
            $logoPath = $fullPath;
        }
    }

    // Fallback al logo por defecto si no existe el de la empresa
    if (!$logoPath) {
        $defaultPath = public_path('logo_factura.png');
        if (file_exists($defaultPath)) {
            $logoPath = $defaultPath;
        }
    }
@endphp

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4/A5 Header --}}
    <div class="header">
        <div class="logo-section">
            @if($logoPath)
                @php
                    $imageData = base64_encode(file_get_contents($logoPath));
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $logoPath);
                    finfo_close($finfo);
                @endphp
                <img src="data:{{ $mimeType }};base64,{{ $imageData }}" alt="Logo Empresa" class="logo-img">
            @endif
        </div>
        
        <div class="company-section">
            <div class="company-name">{{ strtoupper($company->razon_social ?? 'EMPRESA') }}</div>
            <div class="company-details">
                @if($company->direccion)
                    {{ $company->direccion }}<br>
                @endif
                @if($company->distrito || $company->provincia || $company->departamento)
                    {{ $company->distrito ? $company->distrito . ', ' : '' }}{{ $company->provincia ? $company->provincia . ', ' : '' }}{{ $company->departamento }}<br>
                @endif
                @if($company->telefono)
                    TELÉFONO: {{ $company->telefono }}<br>
                @endif
                @if($company->email)
                    EMAIL: {{ $company->email }}<br>
                @endif
                @if($company->web)
                    WEB: {{ $company->web }}
                @endif
            </div>
        </div>
        
        <div class="document-section">
            <div class="factura-box">
                <p><b>RUC {{ $company->ruc ?? 'N/A' }}</b></p>
                <p><b>{{ strtoupper($tipo_documento_nombre ?? 'FACTURA ELECTRÓNICA') }}</b></p>
                <p><b>{{ $document->serie }}-{{ str_pad($document->correlativo, 6, '0', STR_PAD_LEFT) }}</b></p>
            </div>
        </div>
    </div>
@else
    {{-- Ticket Header (58mm/80mm) --}}
    <div class="header">
        {{-- Logo --}}
        @if($logoPath)
            <div class="logo-section-ticket">
                @php
                    $imageData = base64_encode(file_get_contents($logoPath));
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $logoPath);
                    finfo_close($finfo);
                @endphp
                <img src="data:{{ $mimeType }};base64,{{ $imageData }}" alt="Logo" class="logo-img-ticket">
            </div>
        @endif

        {{-- Company Name --}}
        <div class="company-name">{{ strtoupper($company->razon_social ?? 'EMPRESA') }}</div>

        {{-- RUC --}}
        <div class="company-ruc">RUC: {{ $company->ruc ?? '' }}</div>

        {{-- Company Details --}}
        <div class="company-details">
            {{ $company->direccion ?? '' }}@if($company->distrito || $company->provincia), {{ $company->distrito ?? '' }} {{ $company->provincia ?? '' }}@endif<br>
            @if($company->email)Correo: {{ $company->email }}<br>@endif
            @if($company->web)Web: {{ $company->web }}@endif
        </div>

        {{-- Document Title --}}
        <div class="document-title">{{ strtoupper($tipo_documento_nombre ?? 'COMPROBANTE ELECTRÓNICO') }}</div>

        {{-- Document Number --}}
        <div class="document-number">{{ $document->numero_completo ?? $document->serie . '-' . str_pad($document->correlativo, 8, '0', STR_PAD_LEFT) }}</div>
    </div>
@endif