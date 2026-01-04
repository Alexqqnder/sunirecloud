@extends('pdf.layouts.80mm')

@section('content')
    {{-- Header --}}
    @include('pdf.components.header', [
        'company' => $company, 
        'document' => $document, 
        'tipo_documento_nombre' => 'GUÍA DE REMISIÓN ELECTRÓNICA',
        'fecha_emision' => $fecha_emision,
        'format' => '80mm'
    ])

    {{-- Destinatario Info --}}
    @include('pdf.components.dispatch-client-info', [
        'destinatario' => $destinatario,
        'format' => '80mm',
        'fecha_emision' => $fecha_emision,
        'fecha_traslado' => $fecha_traslado,
        'peso_total_formatted' => $peso_total_formatted ?? '0.000 KGM'
    ])

    {{-- Transport Info --}}
    @include('pdf.components.dispatch-transport-info', [
        'document' => $document,
        'motivo_traslado' => $motivo_traslado ?? 'VENTA',
        'modalidad_traslado' => $modalidad_traslado ?? 'TRANSPORTE PRIVADO',
        'peso_total_formatted' => $peso_total_formatted ?? '0.000 KGM',
        'format' => '80mm'
    ])

    {{-- Items Table --}}
    @include('pdf.components.dispatch-items-table', [
        'detalles' => $detalles,
        'format' => '80mm'
    ])

    {{-- Observations --}}
    @if($document->observaciones ?? null)
    @include('pdf.components.dispatch-observations', [
        'observaciones' => $document->observaciones,
        'format' => '80mm'
    ])
    @endif

    {{-- Additional Contacts --}}
    @include('pdf.components.additional-contacts', [
        'company' => $company,
        'format' => '80mm'
    ])

    {{-- Footer Message --}}
    @include('pdf.components.footer-message', [
        'company' => $company,
        'format' => '80mm'
    ])

    {{-- Footer --}}
    @include('pdf.components.qr-footer', [
        'qr_code' => null,
        'hash' => $hash ?? null,
        'format' => '80mm'
    ])
@endsection