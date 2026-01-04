@extends('pdf.layouts.a4')

@section('content')
    {{-- HEADER --}}
    @include('pdf.components.header', [
        'company' => $company,
        'document' => $document,
        'tipo_documento_nombre' => $tipo_documento_nombre,
        'fecha_emision' => $fecha_emision,
        'format' => 'a4'
    ])

    {{-- CLIENT INFO --}}
    @include('pdf.components.client-info', [
        'client' => $client,
        'format' => 'a4',
        'fecha_emision' => $fecha_emision,
        'totales' => $totales ?? []
    ])

    {{-- ITEMS TABLE --}}
    @include('pdf.components.items-table', [
        'detalles' => $detalles,
        'format' => 'a4'
    ])

    {{-- TOTAL IN WORDS --}}
    @include('pdf.components.total-letras', [
        'total_en_letras' => $total_en_letras,
        'format' => 'a4'
    ])

    {{-- TOTALS --}}
    @include('pdf.components.totals-original', [
        'document' => $document,
        'format' => 'a4',
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'fecha_emision' => $fecha_emision,
        'total_en_letras' => $total_en_letras,
        'totales' => $totales
    ])

    {{-- OBSERVACIONES --}}
    @if(!empty($document->observaciones))
        <div style="margin-top: 10px; padding: 5px; border: 1px solid #ddd; border-radius: 3px; background-color: #f9f9f9;">
            <strong style="font-size: 8px;">OBSERVACIONES:</strong>
            <p style="font-size: 7px; margin: 3px 0 0 0;">{{ $document->observaciones }}</p>
        </div>
    @endif

    {{-- FOOTER MESSAGE --}}
    <div style="margin-top: 15px; padding: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #666;">
        <p style="margin: 0;">
            <strong>NOTA DE VENTA - DOCUMENTO NO TRIBUTARIO</strong><br>
            Este documento no tiene validez fiscal ante SUNAT
        </p>
    </div>
@endsection
