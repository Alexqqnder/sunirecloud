@extends('pdf.layouts.base')

@section('format-styles')
    <style>
        /* ================= BASE ================= */
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 19cm;
            margin: 0.5cm auto;
            padding: 0.5cm;
            box-sizing: border-box;
            border: 1px solid #000;
            border-radius: 3px;
        }

        /* ================= HEADER ================= */
        .header {
            display: table;
            width: 100%;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
            table-layout: fixed;
        }

        .header>div {
            display: table-cell;
            vertical-align: top;
            padding: 2px;
        }

        .logo-section {
            width: 25%;
            text-align: left;
            /* border: 1px solid #000; */
        }

        .logo-img {
            width: 145px;
            height: 78;
            object-fit: contain;
            vertical-align: top;
            margin-right: 5px;
           /*  border: 1px solid #000; */
        }

        .company-section {
            width: 47%;
            text-align: left;
            padding: 0 13px;
        }

        .company-name {
            margin: 0 0 3px 0;
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }

        .company-details {
            line-height: 1.2;
            margin: 0;
            font-size: 10px;
            color: #333;
        }

        .document-section {
            width: 25%;
            text-align: center;
            vertical-align: top;
        }

        .factura-box {
            border: 1px solid #000;
            border-radius: 3px;
            padding: 8px;
            font-size: 11px;
            background-color: #fff;
            display: inline-block;
            min-width: 160px;
        }

        .factura-box p {
            margin: 2px 0;
            font-weight: bold;
        }

        /* ================= CLIENT INFO ================= */
        .client-info {
            margin-top: 3px;
            margin-bottom: 3px;
            display: table;
            width: 100%;
            font-size: 10px;
            table-layout: fixed;
        }

        .client-info>div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 2px;
        }

        .client-info p {
            line-height: 1.3;
            margin: 0;
            padding: 2px 0;
        }

        /* ================= TABLA PRINCIPAL ================= */
        .items-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 8px;
            border: 1px solid #000;
            /* marco exterior */
            border-radius: 3px;
            margin-bottom: 2px;
        }

        .items-table thead {
            background-color: #f0f0f0;
        }

        .items-table th,
        .items-table td {
            border-right: 1px solid #000;
            padding: 2px;
            text-align: left;
        }

        .items-table thead th {
            border-bottom: 1px solid #000;
        }

     /* Última fila sin borde inferior */
        .items-table tbody tr:first-child th {
            border-right: none
        }

        /* Última columna sin borde derecho */
        .items-table th:last-child,
        .items-table td:last-child {
            border-right: none;
        }

        /* Última fila sin borde inferior */
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Header sin borde superior */
        .items-table thead th {
            border-top: none;
        }

        /* Esquinas redondeadas sutiles */
        .items-table thead th:first-child {
            border-top-left-radius: 3px;
        }

        .items-table thead th:last-child {
            border-top-right-radius: 3px;
        }

        .items-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 3px;
        }

        .items-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 3px;
        }

        /* Columnas numéricas alineadas a la derecha */
        .items-table th:nth-child(5),
        .items-table th:nth-child(6),
        .items-table td:nth-child(5),
        .items-table td:nth-child(6), 
        .items-table td:nth-child(7), {
            text-align: right;
        }

        /* ================= SON EN LETRAS ================= */
        .en-letras {
            margin-top: 2px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
            border-radius: 3px;
        }

        .en-letras td {
            text-align: center;
            font-weight: bold;
            padding: 2px;
            font-size: 8px;
            border: none;
        }

        /* ================= TOTALES ================= */
        .totals-table {
            margin-top: 2px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
            border-radius: 3px;
        }

        .totals-table td {
            padding: 1px 4px;
            font-size: 8px;
            vertical-align: top;
            line-height: 1.0;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .totals-table td:last-child {
            border-right: none;
        }

        .totals-table tr:last-child td {
            border-bottom: none;
        }

        .totals-table .label {
            text-align: right;
            font-weight: bold;
            width: 150px;
        }

        .totals-table .resaltado {
            background: #f0f0f0;
            font-weight: bold;
        }

        /* Info + QR en misma celda */
        .qr-info-container {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

     /*    .qr-section {
            display: table-cell;
            width: 80;
            vertical-align: top;
            text-align: center;
            padding-right: 5px;
        } */

        .qr-section {
            display: table-cell;
            width: 85px;
            vertical-align: top;
            text-align: center;
            padding-right: 5px;
        }

        .qr-section img {
            width: 85px;
            height: 85px;
            display: block;
            margin: 0 auto;
        }

        .info-footer {
            display: table-cell;
            font-size: 7px;
            text-align: left;
            vertical-align: top;
            padding-left: 5px;
            line-height: 1.2;
        }

        /* ================= FOOTER EXTRA ================= */
        .footer {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #000;
            border-radius: 3px;
            background-color: #f9f9f9;
            font-size: 10px;
            line-height: 1.4;
        }

        /* ================= PAYMENT METHODS ================= */
        .payment-methods {
            margin-top: 3px;
            padding: 3px 5px;
            border: 1px solid #000;
            border-radius: 3px;
            font-size: 7px;
            page-break-inside: avoid;
        }

        .payment-methods-title {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
            text-align: center;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        .payment-section {
            margin-bottom: 3px;
        }

        .payment-section-title {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 2px;
            color: #333;
        }

        .payment-item {
            margin-bottom: 2px;
            padding-left: 3px;
            line-height: 1.2;
        }

        /* Layout compacto de 2 columnas para cuentas */
        .payment-section-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .payment-column {
            display: table-cell;
            width: 50%;
            padding: 0 3px;
            vertical-align: top;
        }

        /* ================= ADDITIONAL CONTACTS ================= */
        .additional-contacts {
            margin-top: 3px;
            padding: 3px 5px;
            border: 1px solid #000;
            border-radius: 3px;
            font-size: 7px;
            display: table;
            width: 100%;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .contacts-section,
        .social-section {
            display: table-cell;
            width: 50%;
            padding: 3px;
            vertical-align: top;
            line-height: 1.2;
        }

        /* ================= FOOTER MESSAGE ================= */
        .footer-message {
            margin-top: 3px;
            padding: 3px 5px;
            border: 1px solid #000;
            border-radius: 3px;
            font-size: 7px;
            line-height: 1.2;
            page-break-inside: avoid;
        }

        .mensaje-personalizado {
            font-weight: bold;
            text-align: center;
            margin-bottom: 3px;
            font-size: 9px;
        }

        .terminos-condiciones,
        .politica-garantia {
            margin-top: 3px;
            font-size: 7px;
        }

        /* ================= PRINT ================= */
        @media print {
            body {
                margin: 0;
            }

            .container {
                border: none;
                padding: 0.5cm;
                margin: 0;
            }
        }
    </style>
@endsection

@section('body-content')
    <div class="container">
        @yield('content')
    </div>
@endsection
