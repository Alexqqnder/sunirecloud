{{-- PDF Credit Installments and Detraction Component --}}
{{-- Props: $document, $format --}}

@php
    $isCredit = isset($document->forma_pago_tipo) && $document->forma_pago_tipo === 'Credito';
    $hasDetraccion = isset($document->detraccion) && !empty($document->detraccion);
    $hasInstallments = $isCredit && isset($document->forma_pago_cuotas) && !empty($document->forma_pago_cuotas);

    // Parse cuotas if they exist
    $cuotas = [];
    if ($hasInstallments) {
        $cuotas = is_array($document->forma_pago_cuotas)
            ? $document->forma_pago_cuotas
            : json_decode($document->forma_pago_cuotas, true);
        $cuotas = $cuotas ?? [];
    }

    // Parse detraccion if exists
    $detraccion = null;
    if ($hasDetraccion) {
        $detraccion = is_array($document->detraccion)
            ? $document->detraccion
            : json_decode($document->detraccion, true);
    }
@endphp

@if($hasInstallments || $hasDetraccion)
    @if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
        {{-- A4/A5 Format - Compact Design --}}
        <div style="margin-top: 5px;">
            @if($hasInstallments && $hasDetraccion)
                {{-- Combined Layout: Both in one row --}}
                <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                    <tr>
                        {{-- Left Column: Payment Schedule --}}
                        <td style="width: 50%; vertical-align: top; padding-right: 5px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                                <thead>
                                    <tr>
                                        <th colspan="3" style="padding: 3px; text-align: left; border: 1px solid #000; font-weight: bold; font-size: 8px;">
                                            CRONOGRAMA DE PAGOS
                                        </th>
                                    </tr>
                                    <tr>
                                        <th style="padding: 2px; border: 1px solid #000; text-align: center; font-size: 7px;">N°</th>
                                        <th style="padding: 2px; border: 1px solid #000; text-align: center; font-size: 7px;">Fecha</th>
                                        <th style="padding: 2px; border: 1px solid #000; text-align: right; font-size: 7px;">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cuotas as $index => $cuota)
                                        <tr>
                                            <td style="padding: 2px 4px; border: 1px solid #000; text-align: center; font-size: 7px;">{{ $index + 1 }}</td>
                                            <td style="padding: 2px 4px; border: 1px solid #000; text-align: center; font-size: 7px;">
                                                @php
                                                    $fechaPago = $cuota['fecha_pago'] ?? null;
                                                    if ($fechaPago) {
                                                        try {
                                                            $fechaPago = \Carbon\Carbon::parse($fechaPago)->format('d/m/Y');
                                                        } catch (\Exception $e) {
                                                            $fechaPago = $cuota['fecha_pago'];
                                                        }
                                                    }
                                                @endphp
                                                {{ $fechaPago ?? 'N/A' }}
                                            </td>
                                            <td style="padding: 2px 4px; border: 1px solid #000; text-align: right; font-size: 7px;">
                                                {{ number_format($cuota['monto'] ?? 0, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>

                        {{-- Right Column: Detraction Info --}}
                        <td style="width: 50%; vertical-align: top; padding-left: 5px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                                <thead>
                                    <tr>
                                        <th colspan="2" style="padding: 3px; text-align: left; border: 1px solid #000; font-weight: bold; font-size: 8px;">
                                            DETRACCION (SPOT)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-size: 7px; width: 45%;">Código:</td>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-size: 7px;">{{ $detraccion['codigo_bien_servicio'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-size: 7px;">Porcentaje:</td>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-size: 7px;">{{ number_format($detraccion['porcentaje'] ?? 0, 2) }}%</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold; font-size: 7px;">Monto Detracción:</td>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold; font-size: 7px;">
                                            {{ number_format($detraccion['monto'] ?? $document->mto_detraccion ?? 0, 2) }}
                                        </td>
                                    </tr>
                                    @if(!empty($detraccion['cuenta_banco']))
                                    <tr>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-size: 7px;">Cta. Bco.:</td>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-size: 7px;">{{ $detraccion['cuenta_banco'] }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold; font-size: 7px;">Pago Proveedor:</td>
                                        <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold; font-size: 7px;">
                                            @php
                                                $montoDetraccion = $detraccion['monto'] ?? $document->mto_detraccion ?? 0;
                                                $totalFactura = $document->mto_imp_venta ?? 0;
                                                $montoProveedor = $totalFactura - $montoDetraccion;
                                            @endphp
                                            {{ number_format($montoProveedor, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div style="font-size: 6px; text-align: center; margin-top: 2px; border: 1px solid #000; padding: 2px;">
                                * Depositar detracción en 5 días hábiles
                            </div>
                        </td>
                    </tr>
                </table>

            @elseif($hasInstallments)
                {{-- Only Payment Schedule --}}
                <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                    <thead>
                        <tr>
                            <th colspan="3" style="padding: 3px; text-align: left; border: 1px solid #000; font-weight: bold;">
                                CRONOGRAMA DE PAGOS
                            </th>
                        </tr>
                        <tr>
                            <th style="padding: 2px; border: 1px solid #000; text-align: center; width: 10%;">N°</th>
                            <th style="padding: 2px; border: 1px solid #000; text-align: center; width: 30%;">Fecha de Pago</th>
                            <th style="padding: 2px; border: 1px solid #000; text-align: right; width: 60%;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cuotas as $index => $cuota)
                            <tr>
                                <td style="padding: 2px 4px; border: 1px solid #000; text-align: center;">{{ $index + 1 }}</td>
                                <td style="padding: 2px 4px; border: 1px solid #000; text-align: center;">
                                    @php
                                        $fechaPago = $cuota['fecha_pago'] ?? null;
                                        if ($fechaPago) {
                                            try {
                                                $fechaPago = \Carbon\Carbon::parse($fechaPago)->format('d/m/Y');
                                            } catch (\Exception $e) {
                                                $fechaPago = $cuota['fecha_pago'];
                                            }
                                        }
                                    @endphp
                                    {{ $fechaPago ?? 'N/A' }}
                                </td>
                                <td style="padding: 2px 4px; border: 1px solid #000; text-align: right;">
                                    {{ $cuota['moneda'] ?? 'PEN' }} {{ number_format($cuota['monto'] ?? 0, 2) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2" style="padding: 3px 4px; border: 1px solid #000; text-align: right; font-weight: bold;">TOTAL:</td>
                            <td style="padding: 3px 4px; border: 1px solid #000; text-align: right; font-weight: bold;">
                                @php
                                    $totalCuotas = array_sum(array_column($cuotas, 'monto'));
                                    $monedaCuota = $cuotas[0]['moneda'] ?? 'PEN';
                                @endphp
                                {{ $monedaCuota }} {{ number_format($totalCuotas, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

            @elseif($hasDetraccion)
                {{-- Only Detraction Info --}}
                <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                    <thead>
                        <tr>
                            <th colspan="2" style="padding: 3px; text-align: left; border: 1px solid #000; font-weight: bold;">
                                INFORMACIÓN DE DETRACCIÓN (SPOT)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 2px 4px; border: 1px solid #000; width: 35%;">Código Bien/Servicio:</td>
                            <td style="padding: 2px 4px; border: 1px solid #000;">{{ $detraccion['codigo_bien_servicio'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 4px; border: 1px solid #000;">Porcentaje de Detracción:</td>
                            <td style="padding: 2px 4px; border: 1px solid #000;">{{ number_format($detraccion['porcentaje'] ?? 0, 2) }}%</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold;">Monto a Detraer:</td>
                            <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold;">
                                {{ $document->moneda ?? 'PEN' }} {{ number_format($detraccion['monto'] ?? $document->mto_detraccion ?? 0, 2) }}
                            </td>
                        </tr>
                        @if(!empty($detraccion['cuenta_banco']))
                        <tr>
                            <td style="padding: 2px 4px; border: 1px solid #000;">Cuenta Banco de la Nación:</td>
                            <td style="padding: 2px 4px; border: 1px solid #000;">{{ $detraccion['cuenta_banco'] }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold;">Monto a Pagar al Proveedor:</td>
                            <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold;">
                                @php
                                    $montoDetraccion = $detraccion['monto'] ?? $document->mto_detraccion ?? 0;
                                    $totalFactura = $document->mto_imp_venta ?? 0;
                                    $montoProveedor = $totalFactura - $montoDetraccion;
                                @endphp
                                {{ $document->moneda ?? 'PEN' }} {{ number_format($montoProveedor, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style="font-size: 7px; text-align: center; margin-top: 2px; border: 1px solid #000; padding: 2px;">
                    <strong>IMPORTANTE:</strong> El cliente debe depositar el monto de detracción en la cuenta del Banco de la Nación dentro de los 5 días hábiles. El saldo restante se paga directamente al proveedor.
                </div>
            @endif
        </div>

    @elseif(in_array($format, ['80mm', '58mm']))
        {{-- Ticket Format --}}
        <div style="margin-top: 8px; border-top: 1px dashed #000; padding-top: 5px;">
            @if($hasInstallments)
                <div style="font-size: 10px; font-weight: bold; text-align: center; margin-bottom: 5px;">
                    CRONOGRAMA DE PAGOS
                </div>
                <table style="width: 100%; font-size: 9px; margin-bottom: 8px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 2px; border-bottom: 1px solid #000;">Cuota</th>
                            <th style="text-align: center; padding: 2px; border-bottom: 1px solid #000;">Fecha</th>
                            <th style="text-align: right; padding: 2px; border-bottom: 1px solid #000;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cuotas as $index => $cuota)
                            <tr>
                                <td style="padding: 2px;">{{ $index + 1 }}</td>
                                <td style="text-align: center; padding: 2px;">
                                    @php
                                        $fechaPago = $cuota['fecha_pago'] ?? null;
                                        if ($fechaPago) {
                                            try {
                                                $fechaPago = \Carbon\Carbon::parse($fechaPago)->format('d/m/Y');
                                            } catch (\Exception $e) {
                                                $fechaPago = $cuota['fecha_pago'];
                                            }
                                        }
                                    @endphp
                                    {{ $fechaPago ?? 'N/A' }}
                                </td>
                                <td style="text-align: right; padding: 2px;">
                                    {{ number_format($cuota['monto'] ?? 0, 2) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr style="border-top: 1px solid #000; font-weight: bold;">
                            <td colspan="2" style="text-align: right; padding: 2px;">TOTAL:</td>
                            <td style="text-align: right; padding: 2px;">
                                @php
                                    $totalCuotas = array_sum(array_column($cuotas, 'monto'));
                                @endphp
                                {{ number_format($totalCuotas, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif

            @if($hasDetraccion)
                <div style="border-top: 1px dashed #000; padding-top: 5px; margin-top: 5px;">
                    <div style="font-size: 10px; font-weight: bold; text-align: center; margin-bottom: 5px;">
                        DETRACCION (SPOT)
                    </div>
                    <table style="width: 100%; font-size: 9px;">
                        <tr>
                            <td style="padding: 1px;"><strong>Codigo:</strong></td>
                            <td style="text-align: right; padding: 1px;">{{ $detraccion['codigo_bien_servicio'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px;"><strong>Porcentaje:</strong></td>
                            <td style="text-align: right; padding: 1px;">{{ number_format($detraccion['porcentaje'] ?? 0, 2) }}%</td>
                        </tr>
                        <tr style="border-top: 1px solid #000;">
                            <td style="padding: 1px;"><strong>Monto Detraccion:</strong></td>
                            <td style="text-align: right; padding: 1px; font-weight: bold;">
                                {{ number_format($detraccion['monto'] ?? $document->mto_detraccion ?? 0, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 1px;"><strong>Pago Proveedor:</strong></td>
                            <td style="text-align: right; padding: 1px; font-weight: bold;">
                                @php
                                    $montoDetraccion = $detraccion['monto'] ?? $document->mto_detraccion ?? 0;
                                    $totalFactura = $document->mto_imp_venta ?? 0;
                                    $montoProveedor = $totalFactura - $montoDetraccion;
                                @endphp
                                {{ number_format($montoProveedor, 2) }}
                            </td>
                        </tr>
                        @if(!empty($detraccion['cuenta_banco']))
                        <tr>
                            <td style="padding: 1px;"><strong>Cta. Bco. Nacion:</strong></td>
                            <td style="text-align: right; padding: 1px;">{{ $detraccion['cuenta_banco'] }}</td>
                        </tr>
                        @endif
                    </table>
                    <div style="font-size: 8px; text-align: center; margin-top: 5px; border-top: 1px dashed #000; padding-top: 3px;">
                        Depositar detraccion en 5 dias habiles
                    </div>
                </div>
            @endif
        </div>
    @endif
@endif
