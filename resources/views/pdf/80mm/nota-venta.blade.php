@extends('pdf.layouts.80mm')

@section('content')
    @include('pdf.components.header', [
        'company' => $company,
        'document' => $document,
        'tipo_documento_nombre' => $tipo_documento_nombre,
        'fecha_emision' => $fecha_emision,
        'format' => '80mm'
    ])

    @include('pdf.components.client-info', [
        'client' => $client,
        'format' => '80mm',
        'fecha_emision' => $fecha_emision,
        'totales' => $totales ?? []
    ])

    @include('pdf.components.items-table', [
        'detalles' => $detalles,
        'format' => '80mm'
    ])

    @include('pdf.components.totals-original', [
        'document' => $document,
        'format' => '80mm',
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'fecha_emision' => $fecha_emision,
        'total_en_letras' => $total_en_letras,
        'totales' => $totales
    ])

    @if(!empty($document->observaciones))
        <div style="margin-top: 5px; padding: 3px; border: 1px dashed #000; font-size: 7px;">
            <strong>OBSERVACIONES:</strong> {{ $document->observaciones }}
        </div>
    @endif

    <div style="margin-top: 5px; padding: 3px; border-top: 1px dashed #000; text-align: center; font-size: 6px;">
        <strong>NOTA DE VENTA - DOCUMENTO NO TRIBUTARIO</strong><br>
        No válido para fines fiscales
    </div>
@endsection
