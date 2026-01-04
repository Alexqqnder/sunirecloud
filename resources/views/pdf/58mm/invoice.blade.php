@extends('pdf.layouts.58mm')

@section('content')
    {{-- Header --}}
    @include('pdf.components.header', [
        'company' => $company,
        'document' => $document,
        'tipo_documento_nombre' => $tipo_documento_nombre,
        'fecha_emision' => $fecha_emision,
        'format' => '58mm'
    ])

    {{-- Client Info --}}
    @include('pdf.components.client-info', [
        'client' => $client,
        'fecha_emision' => $fecha_emision,
        'format' => '58mm'
    ])

    {{-- Items Table --}}
    @include('pdf.components.items-table', [
        'detalles' => $detalles,
        'format' => '58mm'
    ])

    {{-- Totals with QR --}}
    @include('pdf.components.totals-original', [
        'document' => $document,
        'format' => '58mm',
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'fecha_emision' => $fecha_emision,
        'total_en_letras' => $total_en_letras ?? '',
        'totales' => $totales ?? []
    ])

    {{-- Credit Installments and Detraction --}}
    @include('pdf.components.credit-installments', [
        'document' => $document,
        'format' => '58mm'
    ])

    {{-- Payment Methods --}}
    @include('pdf.components.payment-methods', [
        'company' => $company,
        'format' => '58mm'
    ])

    {{-- Additional Contacts --}}
    @include('pdf.components.additional-contacts', [
        'company' => $company,
        'format' => '58mm'
    ])

    {{-- Footer Message --}}
    @include('pdf.components.footer-message', [
        'company' => $company,
        'format' => '58mm'
    ])

    {{-- Footer --}}
    @include('pdf.components.qr-footer', [
        'qr_code' => null,
        'hash' => $hash ?? null,
        'format' => '58mm'
    ])
@endsection
