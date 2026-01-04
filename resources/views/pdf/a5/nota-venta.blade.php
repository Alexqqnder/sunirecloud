@extends('pdf.layouts.a5')

@section('content')
    @include('pdf.components.header', [
        'company' => $company,
        'document' => $document,
        'tipo_documento_nombre' => $tipo_documento_nombre,
        'fecha_emision' => $fecha_emision,
        'format' => 'a5'
    ])

    @include('pdf.components.client-info', [
        'client' => $client,
        'format' => 'a5',
        'fecha_emision' => $fecha_emision,
        'totales' => $totales ?? []
    ])

    @include('pdf.components.items-table', [
        'detalles' => $detalles,
        'format' => 'a5'
    ])

    @include('pdf.components.total-letras', [
        'total_en_letras' => $total_en_letras,
        'format' => 'a5'
    ])

    @include('pdf.components.totals-original', [
        'document' => $document,
        'format' => 'a5',
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'fecha_emision' => $fecha_emision,
        'total_en_letras' => $total_en_letras,
        'totales' => $totales
    ])

    @if(!empty($document->observaciones))
        <div style="margin-top: 8px; padding: 4px; border: 1px solid #ddd; background-color: #f9f9f9;">
            <strong style="font-size: 7px;">OBSERVACIONES:</strong>
            <p style="font-size: 6px; margin: 2px 0 0 0;">{{ $document->observaciones }}</p>
        </div>
    @endif

    <div style="margin-top: 10px; padding: 8px; border-top: 1px solid #ddd; text-align: center; font-size: 6px; color: #666;">
        <p style="margin: 0;">
            <strong>NOTA DE VENTA - DOCUMENTO NO TRIBUTARIO</strong><br>
            Este documento no tiene validez fiscal ante SUNAT
        </p>
    </div>
@endsection
