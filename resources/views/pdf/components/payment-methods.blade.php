{{-- PDF Payment Methods Component --}}
{{-- Props: $company, $format --}}

@php
    $hasCuentas = !empty($company->cuentas_bancarias) && $company->mostrar_cuentas_en_pdf;
    $hasBilleteras = !empty($company->billeteras_digitales) && $company->mostrar_billeteras_en_pdf;
    $shouldShow = $hasCuentas || $hasBilleteras;
@endphp

@if($shouldShow)
    @if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
        {{-- A4/A5 Payment Methods --}}
        <div class="payment-methods">
            <div class="payment-methods-title">MEDIOS DE PAGO</div>

            <div class="payment-section-grid">
                @if($hasCuentas)
                    <div class="payment-column">
                        <div class="payment-section-title">Cuentas Bancarias</div>
                        @foreach($company->cuentas_bancarias as $cuenta)
                            @if($cuenta['activo'] ?? true)
                                <div class="payment-item">
                                    <b>{{ $cuenta['banco'] ?? '' }}</b>
                                    ({{ $cuenta['moneda'] ?? 'PEN' }})
                                    @if(isset($cuenta['numero']))
                                        <br>{{ $cuenta['numero'] }}
                                    @endif
                                    @if(isset($cuenta['cci']))
                                        <br>CCI: {{ $cuenta['cci'] }}
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if($hasBilleteras)
                    <div class="payment-column">
                        <div class="payment-section-title">Billeteras Digitales</div>
                        @foreach($company->billeteras_digitales as $billetera)
                            @if($billetera['activo'] ?? true)
                                <div class="payment-item">
                                    <b>{{ $billetera['tipo'] ?? '' }}:</b> {{ $billetera['numero'] ?? '' }}
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Ticket Payment Methods (80mm/58mm) --}}
        <div class="payment-methods-ticket">
            @if($hasCuentas)
                <div class="section-title-ticket">--- CUENTAS BANCARIAS ---</div>
                @foreach($company->cuentas_bancarias as $cuenta)
                    @if($cuenta['activo'] ?? true)
                        <div class="payment-item-ticket">
                            <b>{{ $cuenta['banco'] ?? '' }}</b> ({{ $cuenta['moneda'] ?? 'PEN' }})<br>
                            @if(isset($cuenta['numero']))
                                Nro: {{ $cuenta['numero'] }}<br>
                            @endif
                            @if(isset($cuenta['cci']))
                                CCI: {{ $cuenta['cci'] }}
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif

            @if($hasBilleteras)
                <div class="section-title-ticket">--- BILLETERAS DIGITALES ---</div>
                @foreach($company->billeteras_digitales as $billetera)
                    @if($billetera['activo'] ?? true)
                        <div class="payment-item-ticket">
                            <b>{{ $billetera['tipo'] ?? '' }}:</b> {{ $billetera['numero'] ?? '' }}
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    @endif
@endif
