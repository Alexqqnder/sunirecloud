{{-- PDF Footer Message Component --}}
{{-- Props: $company, $format --}}

@php
    $hasMensaje = !empty($company->mensaje_pdf);
    $hasTerminos = !empty($company->terminos_condiciones_pdf);
    $hasGarantia = !empty($company->politica_garantia);
@endphp

@if($hasMensaje || $hasTerminos || $hasGarantia)
    @if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
        {{-- A4/A5 Footer Message --}}
        <div class="footer-message">
            @if($hasMensaje)
                <div class="mensaje-personalizado">
                    {{ $company->mensaje_pdf }}
                </div>
            @endif

            @if($hasTerminos || $hasGarantia)
                <div class="terminos-condiciones">
                    @if($hasTerminos)
                        <b>Términos:</b> {{ Str::limit($company->terminos_condiciones_pdf, 150) }}
                    @endif
                    @if($hasTerminos && $hasGarantia)
                        |
                    @endif
                    @if($hasGarantia)
                        <b>Garantía:</b> {{ Str::limit($company->politica_garantia, 100) }}
                    @endif
                </div>
            @endif
        </div>
    @else
        {{-- Ticket Footer Message --}}
        <div class="footer-message-ticket">
            @if($hasMensaje)
                <div class="mensaje-ticket">
                    {{ $company->mensaje_pdf }}
                </div>
            @endif

            @if($hasTerminos)
                <div class="terminos-ticket">
                    <b>Términos:</b> {{ Str::limit($company->terminos_condiciones_pdf, 100) }}
                </div>
            @endif

            @if($hasGarantia)
                <div class="terminos-ticket">
                    <b>Garantía:</b> {{ Str::limit($company->politica_garantia, 80) }}
                </div>
            @endif
        </div>
    @endif
@endif
