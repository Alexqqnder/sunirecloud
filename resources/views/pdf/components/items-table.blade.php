{{-- PDF Items Table Component --}}
{{-- Props: $detalles, $format --}}
@php
     $maxFilas = in_array($format, ['a5', 'A5']) ? 8 : 15;
    $contador = count($detalles);
@endphp

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4/A5 Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>Nº</th>
                <th>CÓDIGO</th>
                <th>DESCRIPCIÓN</th>
                <th>UNIDAD</th>
                <th>CANT.</th>
                <th>P. UNIT.</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            {{-- Items reales --}}
            @foreach($detalles as $index => $detalle)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detalle['codigo'] ?? '' }}</td>
                    <td>{{ $detalle['descripcion'] ?? '' }}</td>
                    <td>{{ $detalle['unidad'] ?? 'NIU' }}</td>
                    <td>{{ number_format($detalle['cantidad'] ?? 0, 2) }}</td>
                    <td>{{ number_format($detalle['mto_precio_unitario'] ?? 0, 2) }}</td>
                    <td>{{ number_format(($detalle['cantidad'] ?? 0) * ($detalle['mto_precio_unitario'] ?? 0), 2) }}</td>
                </tr>
            @endforeach

            {{-- Filas vacías --}}
            @for($i = $contador; $i < $maxFilas; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>
@else
    {{-- Ticket Items Table (58mm/80mm) - COMPACTO BLANCO Y NEGRO --}}
    <div style="margin: 4px 0; border-top: 2px solid #000; border-bottom: 1px solid #000; font-family: 'Courier New', monospace;">
        {{-- Header --}}
        <table style="width: 100%; border-collapse: collapse; font-size: 7px; font-weight: bold; border-bottom: 1px solid #000;">
            <tr>
                <th style="padding: 2px; text-align: left; width: {{ $format === '80mm' ? '15%' : '18%' }};">CANT</th>
                <th style="padding: 2px; text-align: left; width: {{ $format === '80mm' ? '50%' : '45%' }};">DESCRIPCIÓN</th>
                <th style="padding: 2px; text-align: right; width: {{ $format === '80mm' ? '17%' : '19%' }};">P.U.</th>
                <th style="padding: 2px; text-align: right; width: {{ $format === '80mm' ? '18%' : '18%' }};">TOTAL</th>
            </tr>
        </table>

        {{-- Items --}}
        <table style="width: 100%; border-collapse: collapse; font-size: 7px;">
            @foreach($detalles as $index => $detalle)
                <tr style="border-bottom: 1px dashed #ccc;">
                    <td style="padding: 2px; text-align: center; width: {{ $format === '80mm' ? '15%' : '18%' }};">{{ number_format($detalle['cantidad'] ?? 0, 0) }}</td>
                    <td style="padding: 2px; text-align: left; width: {{ $format === '80mm' ? '50%' : '45%' }}; word-wrap: break-word;">
                        @if($format === '80mm')
                            {{ Str::limit($detalle['descripcion'] ?? '', 35) }}
                        @else
                            {{ Str::limit($detalle['descripcion'] ?? '', 18) }}
                        @endif
                    </td>
                    <td style="padding: 2px; text-align: right; width: {{ $format === '80mm' ? '17%' : '19%' }};">{{ number_format($detalle['mto_precio_unitario'] ?? 0, 2) }}</td>
                    <td style="padding: 2px; text-align: right; width: {{ $format === '80mm' ? '18%' : '18%' }}; font-weight: bold;">{{ number_format(($detalle['cantidad'] ?? 0) * ($detalle['mto_precio_unitario'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endif
