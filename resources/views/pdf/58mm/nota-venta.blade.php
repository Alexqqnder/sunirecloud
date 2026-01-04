@extends('pdf.layouts.58mm')

@section('content')
    @include('pdf.components.header', [
        'company' => $company,
        'document' => $document,
        'tipo_documento_nombre' => $tipo_documento_nombre,
        'fecha_emision' => $fecha_emision,
        'format' => '58mm'
    ])

    @include('pdf.components.client-info', [
        'client' => $client,
        'format' => '58mm',
        'fecha_emision' => $fecha_emision,
        'totales' => $totales ?? []
    ])

    @include('pdf.components.items-table', [
        'detalles' => $detalles,
        'format' => '58mm'
    ])

    @include('pdf.components.totals-original', [
        'document' => $document,
        'format' => '58mm',
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'fecha_emision' => $fecha_emision,
        'total_en_letras' => $total_en_letras,
        'totales' => $totales
    ])

    @if(!empty($document->observaciones))
        <div style="margin-top: 5px; padding: 2px; border: 1px dashed #000; font-size: 6px;">
            <strong>OBS:</strong> {{ Str::limit($document->observaciones, 100) }}
        </div>
    @endif

    <div style="margin-top: 5px; padding: 2px; border-top: 1px dashed #000; text-align: center; font-size: 5px;">
        <strong>NOTA DE VENTA - NO TRIBUTARIO</strong>
    </div>
@endsection
