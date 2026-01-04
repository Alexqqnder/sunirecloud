{{-- PDF Additional Contacts Component --}}
{{-- Props: $company, $format --}}

@php
    $hasAdditionalContacts = $company->mostrar_contactos_adicionales_en_pdf && (
        $company->telefono_2 ||
        $company->telefono_3 ||
        $company->whatsapp ||
        $company->email_ventas ||
        $company->email_soporte
    );

    $hasRedesSociales = $company->mostrar_redes_sociales_en_pdf && (
        $company->facebook ||
        $company->instagram ||
        $company->twitter ||
        $company->linkedin ||
        $company->tiktok
    );
@endphp

@if($hasAdditionalContacts || $hasRedesSociales)
    @if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
        {{-- A4/A5 Additional Contacts --}}
        <div class="additional-contacts">
            @if($hasAdditionalContacts)
                <div class="contacts-section">
                    <b>CONTACTOS ADICIONALES</b><br>
                    @if($company->telefono_2)
                        <b>Tel. 2:</b> {{ $company->telefono_2 }}<br>
                    @endif
                    @if($company->telefono_3)
                        <b>Tel. 3:</b> {{ $company->telefono_3 }}<br>
                    @endif
                    @if($company->whatsapp)
                        <b>WhatsApp:</b> {{ $company->whatsapp }}<br>
                    @endif
                    @if($company->email_ventas)
                        <b>Ventas:</b> {{ $company->email_ventas }}<br>
                    @endif
                    @if($company->email_soporte)
                        <b>Soporte:</b> {{ $company->email_soporte }}
                    @endif
                </div>
            @endif

            @if($hasRedesSociales)
                <div class="social-section">
                    <b>REDES SOCIALES</b><br>
                    @if($company->facebook)
                        <b>Facebook:</b> {{ str_replace(['https://', 'http://', 'www.'], '', $company->facebook) }}<br>
                    @endif
                    @if($company->instagram)
                        <b>Instagram:</b> {{ str_replace(['https://', 'http://', 'www.'], '', $company->instagram) }}<br>
                    @endif
                    @if($company->twitter)
                        <b>Twitter:</b> {{ str_replace(['https://', 'http://', 'www.'], '', $company->twitter) }}<br>
                    @endif
                    @if($company->linkedin)
                        <b>LinkedIn:</b> {{ str_replace(['https://', 'http://', 'www.'], '', $company->linkedin) }}<br>
                    @endif
                    @if($company->tiktok)
                        <b>TikTok:</b> {{ str_replace(['https://', 'http://', 'www.'], '', $company->tiktok) }}
                    @endif
                </div>
            @endif
        </div>
    @else
        {{-- Ticket Additional Contacts (80mm/58mm) --}}
        <div class="additional-contacts-ticket">
            @if($hasAdditionalContacts)
                <div class="section-title-ticket">--- CONTACTO ---</div>
                @if($company->whatsapp)
                    WhatsApp: {{ $company->whatsapp }}<br>
                @endif
                @if($company->telefono_2)
                    Tel: {{ $company->telefono_2 }}<br>
                @endif
                @if($company->email_ventas)
                    Ventas: {{ $company->email_ventas }}<br>
                @endif
                @if($company->email_soporte)
                    Soporte: {{ $company->email_soporte }}<br>
                @endif
            @endif

            @if($hasRedesSociales)
                <div class="section-title-ticket">--- REDES SOCIALES ---</div>
                @if($company->facebook)
                    FB: {{ str_replace(['https://', 'http://', 'www.facebook.com/'], '', $company->facebook) }}<br>
                @endif
                @if($company->instagram)
                    IG: @{{ str_replace(['https://', 'http://', 'www.instagram.com/', 'instagram.com/'], '', $company->instagram) }}<br>
                @endif
                @if($company->twitter)
                    TW: {{ str_replace(['https://', 'http://', 'www.twitter.com/', 'twitter.com/'], '@', $company->twitter) }}
                @endif
            @endif
        </div>
    @endif
@endif
