@extends('pdf.layouts.a4')

@section('content')
    {{-- Header --}}
    @include('pdf.components.header', [
        'company' => $company, 
        'document' => $document, 
        'tipo_documento_nombre' => $tipo_documento_nombre,
        'fecha_emision' => $fecha_emision,
        'format' => 'a4'
    ])

    {{-- Client Info --}}
    @include('pdf.components.client-info', [
        'client' => $client,
        'format' => 'a4',
        'fecha_emision' => $fecha_emision,
        'fecha_vencimiento' => $fecha_vencimiento ?? null,
        'totales' => $totales ?? []
    ])

    {{-- Items Table --}}
    @include('pdf.components.items-table', [
        'detalles' => $detalles,
        'format' => 'a4'
    ])

    {{-- Total En Letras --}}
    @include('pdf.components.total-letras', [
        'total_en_letras' => $total_en_letras ?? '',
        'totales' => $totales ?? [],
        'format' => 'a4'
    ])

    {{-- Totals with QR --}}
    @include('pdf.components.totals-original', [
        'document' => $document,
        'format' => 'a4',
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'fecha_emision' => $fecha_emision,
        'total_en_letras' => $total_en_letras ?? '',
        'totales' => $totales ?? []
    ])

    {{-- Credit Installments and Detraction --}}
    @include('pdf.components.credit-installments', [
        'document' => $document,
        'format' => 'a4'
    ])

    {{-- Payment Methods --}}
    @include('pdf.components.payment-methods', [
        'company' => $company,
        'format' => 'a4'
    ])

    {{-- Additional Contacts --}}
    @include('pdf.components.additional-contacts', [
        'company' => $company,
        'format' => 'a4'
    ])

    {{-- Footer Message --}}
    @include('pdf.components.footer-message', [
        'company' => $company,
        'format' => 'a4'
    ])

    {{-- Footer --}}
    @include('pdf.components.qr-footer', [
        'qr_code' => null,
        'hash' => $hash ?? null,
        'format' => 'a4'
    ])
@endsection