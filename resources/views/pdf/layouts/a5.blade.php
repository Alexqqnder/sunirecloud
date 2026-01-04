@extends('pdf.layouts.base')

@section('format-styles')
<style>
    /* ================= BASE ================= */
    body {
        font-family: Arial, sans-serif;
        font-size: 9px;
        margin: 0;
        padding: 0;
    }

    .container {
        width: 13.5cm;
        margin: 0.4cm auto;
        padding: 0.4cm;
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

    .header > div {
        display: table-cell;
        vertical-align: top;
        padding: 2px;
    }

    .logo-section {
        width: 25%;
        text-align: left;
    }

    .logo-img {
        width: 90px;
        height: 50px;
        object-fit: contain;
        vertical-align: top;
        margin-right: 5px;
    }

    .company-section {
        width: 50%;
        text-align: left;
        padding: 0 8px;
    }

    .company-name {
        margin: 0 0 2px 0;
        font-size: 12px;
        font-weight: bold;
        color: #000;
    }

    .company-details {
        line-height: 1.2;
        margin: 0;
        font-size: 8px;
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
        padding: 6px;
        font-size: 9px;
        background-color: #fff;
        display: inline-block;
        min-width: 130px;
    }

    .factura-box p {
        margin: 2px 0;
        font-weight: bold;
    }

    /* ================= CLIENT INFO ================= */
    .client-info {
        margin-top: 5px;
        margin-bottom: 5px;
        display: table;
        width: 100%;
        font-size: 8px;
        table-layout: fixed;
    }

    .client-info > div {
        display: table-cell;
        width: 50%;
        vertical-align: top;
        padding: 2px;
    }

    .client-info p {
        line-height: 1.2;
        margin: 0;
        padding: 2px 0;
    }

    /* ================= TABLA PRINCIPAL ================= */
        .items-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 7px;
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
            padding: 2px 3px;
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
        .items-table td:nth-child(7) {
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
        font-size: 7px;
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
        font-size: 7px;
        vertical-align: top;
        line-height: 1.1;
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
        width: 100px;
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

    .qr-section {
        display: table-cell;
        width: 70px;
        vertical-align: top;
        text-align: center;
        padding-right: 5px;
    }

    .qr-section img {
        width: 70px;
        height: 70px;
        display: block;
        margin: 0 auto;
    }

    .info-footer {
        display: table-cell;
        font-size: 6px;
        text-align: left;
        vertical-align: top;
        padding-left: 5px;
        line-height: 1.2;
    }

    /* ================= FOOTER EXTRA ================= */
    .footer {
        margin-top: 10px;
        padding: 8px;
        border: 1px solid #000;
        border-radius: 3px;
        background-color: #f9f9f9;
        font-size: 7px;
        line-height: 1.2;
    }

    /* ================= PRINT ================= */
    @media print {
        body {
            margin: 0;
        }

        .container {
            border: none;
            padding: 0.4cm;
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