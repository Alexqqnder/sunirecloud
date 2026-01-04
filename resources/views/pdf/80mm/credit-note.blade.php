@extends('pdf.layouts.80mm')

@section('content')
    {{-- Header --}}
    @include('pdf.components.header', [
        'company' => $company, 
        'document' => $document, 
        'tipo_documento_nombre' => $tipo_documento_nombre,
        'fecha_emision' => $fecha_emision,
        'format' => '80mm'
    ])

    {{-- Reference Document --}}
    @include('pdf.components.reference-document', [
        'documento_afectado' => $documento_afectado,
        'motivo' => $motivo,
        'format' => '80mm'
    ])

    {{-- Client Info --}}
    @include('pdf.components.client-info', [
        'client' => $client,
        'format' => '80mm',
        'fecha_emision' => $fecha_emision,
        'fecha_vencimiento' => $fecha_vencimiento ?? null,
        'totales' => $totales ?? []
    ])

    {{-- Items Table --}}
    @include('pdf.components.items-table', [
        'detalles' => $detalles,
        'format' => '80mm'
    ])

    {{-- Total En Letras --}}
    @include('pdf.components.total-letras', [
        'total_en_letras' => $total_en_letras ?? '',
        'totales' => $totales ?? [],
        'format' => '80mm'
    ])

    {{-- Totals with QR --}}
    @include('pdf.components.totals-original', [
        'document' => $document,
        'format' => '80mm',
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'fecha_emision' => $fecha_emision,
        'total_en_letras' => $total_en_letras ?? '',
        'totales' => $totales ?? []
    ])

    {{-- Payment Methods --}}
    @include('pdf.components.payment-methods', [
        'company' => $company,
        'format' => '80mm'
    ])

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