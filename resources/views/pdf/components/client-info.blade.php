{{-- PDF Client Info Component --}}
{{-- Props: $client, $format, $fecha_emision (optional) --}}

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4/A5 Client Info --}}
    <div class="client-info">
        <div>
            <p>
                <b>{{ $client['tipo_documento'] == '6' ? 'RUC' : 'DNI' }}:</b> {{ $client['numero_documento'] ?? 'N/A' }}<br>
                <b>CLIENTE:</b> {{ $client['razon_social'] ?? 'CLIENTE' }}<br>
                @if(isset($client['direccion']) && $client['direccion'])
                    <b>DIRECCIÓN:</b> {{ $client['direccion'] }}
                @endif
            </p>
        </div>
        <div>
            <p>
                <b>FECHA EMISIÓN:</b> {{ $fecha_emision }}<br>
                <b>FECHA VENCIMIENTO:</b> {{ $fecha_vencimiento ?? '-' }}<br>
                <b>MONEDA:</b> {{ $totales['moneda_nombre'] ?? 'SOLES' }}
            </p>
        </div>
    </div>
@else
    {{-- Ticket Client Info (58mm/80mm) --}}
    <div class="client-section">
        {{-- Client Name --}}
        <div class="client-name">{{ strtoupper($client['razon_social'] ?? $client['nombre'] ?? 'CLIENTE') }}</div>

        {{-- Separator --}}
        <div class="client-separator">---</div>

        {{-- Document Type and Number --}}
        @if(isset($client['numero_documento']))
            <div class="client-details">
                {{ $client['tipo_documento'] == '6' ? 'RUC' : ($client['tipo_documento'] == '1' ? 'DNI' : 'DOC') }} {{ $client['numero_documento'] }}
            </div>
        @endif

        {{-- Address (if available) --}}
        @if(isset($client['direccion']) && $client['direccion'])
            <div class="client-details break-word">DIR: {{ $client['direccion'] }}</div>
        @endif

        {{-- Date and Time --}}
        @if(isset($fecha_emision))
            <div class="client-details">
                FECHA: {{ $fecha_emision }} HORA: {{ now()->format('H:i:s A') }}
            </div>
        @endif
    </div>
@endif