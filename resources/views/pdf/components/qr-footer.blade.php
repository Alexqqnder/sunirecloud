{{-- PDF QR Code and Footer Component --}}
{{-- Props: $qr_code (optional), $hash (optional), $format --}}

@if(isset($qr_code) && $qr_code)
    <div class="qr-section">
        <div class="qr-code">
            <img src="{{ $qr_code }}"
                 alt="Código QR"
                 style="width: {{ $format === 'a4' ? '80px' : '60px' }}; height: {{ $format === 'a4' ? '80px' : '60px' }};">
        </div>
        <div class="qr-info">
            Representación impresa del comprobante electrónico
        </div>

        {{-- Mostrar Hash debajo del QR --}}
        @if(isset($hash) && $hash)
            <div class="hash-qr" style="margin-top: 5px; font-size: {{ $format === 'a4' ? '7px' : '6px' }}; text-align: center; color: #333; word-break: break-all; font-family: monospace;">
                <strong style="display: block; margin-bottom: 2px;">CÓDIGO HASH:</strong>
                <span style="background-color: #f0f0f0; padding: 3px 5px; border: 1px solid #ddd; display: inline-block; border-radius: 3px;">{{ $hash }}</span>
            </div>
        @endif
    </div>
@endif

@if(isset($hash) || true)
    <div class="footer">
        <div>Autorizado mediante Resolución de Superintendencia Nº 097-2012/SUNAT</div>
        <div>Representación impresa del Comprobante de Pago Electrónico</div>
        
        @if(isset($hash) && $hash)
            <div class="hash-section">
                <strong>HASH CDR:</strong> {{ $hash }}
            </div>
        @endif
        
        <div class="hash-section">
            Consulte su comprobante en: {{ config('app.url', 'https://mi-empresa.com') }}
        </div>
    </div>
@endif