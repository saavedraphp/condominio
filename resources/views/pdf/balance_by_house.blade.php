<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance de Cuenta - {{ $house->address ?? $house->id }}</title>
    <style>
        /* Estilos generales inspirados en tu ejemplo y mejorados */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }

        .report-container {
            max-width: 800px;
            margin: auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            background-color: #f8f9fa;
            padding: 24px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-header h1 {
            margin: 0;
            font-size: 24px;
            color: #212529;
        }

        .card-header p {
            margin: 4px 0 0;
            font-size: 16px;
            color: #6c757d;
        }

        .card-header img {
            max-width: 120px;
            margin-bottom: 15px;
        }

        .report-meta {
            display: flex;
            align-items: center;
            gap: 8px; /* Espacio entre el ícono y el texto */
            margin-top: 12px;
            font-size: 14px;
            color: #6c757d;
        }

        .report-meta svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .card-header .download-button {
            float: right;
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-top: -10px;
        }

        .card-body {
            padding: 24px;
        }

        .owner-info {
            margin-bottom: 24px;
            padding: 15px;
            background-color: #eef7ff;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }

        .owner-info strong {
            display: inline-block;
            width: 100px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 12px;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        table td:nth-child(3),
        table td:nth-child(4),
        table td:nth-child(6) {
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
        }

        table th:nth-child(3),
        table th:nth-child(4),
        table th:nth-child(6) {
            text-align: right;
        }


        .summary {
            border-top: 2px solid #007bff;
            margin-top: 20px;
            padding-top: 20px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .summary-table .label {
            font-size: 14px;
            color: #6c757d;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .summary-table .value {
            font-size: 18px;
            font-weight: bold;
        }

        .summary-table .positive {
            color: #28a745; /* Verde */
        }

        .summary-table .negative {
            color: #dc3545; /* Rojo */
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .summary-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .summary-item .label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .summary-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #212529;
        }

        .summary-item .value.positive {
            color: #28a745;
        }

        .summary-item .value.negative {
            color: #dc3545;
        }
    </style>
</head>
<body>

<div class="report-container">
    <div class="card-header">
        <img src="{{ $attributes['logo_path'] }}" alt="Logo">
        @if(!$isPdf)
            <a href="{{ route('admin.houses.balance.download', $house) }}" class="download-button">Descargar PDF</a>
        @endif
        <h1>Estado de Cuenta</h1>
        <h2>{{$attributes['site_name']}}</h2>
        <p>Balance detallado de movimientos : {{ $reportDate->isoFormat('D [de] MMMM [de] Y') }}</p>

        <!--        <div class="report-meta">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="#6c757d" xmlns="http://www.w3.org/2000/svg"
                 style="vertical-align: middle;">
                <path
                    d="M19 4H5C3.89543 4 3 4.89543 3 6V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V6C21 4.89543 20.1046 4 19 4ZM19 20H5V9H19V20ZM5 7V6H19V7H5Z"/>
                <path d="M8 2H10V5H8V2Z"/>
                <path d="M14 2H16V5H14V2Z"/>
            </svg>
            <span>
                Fecha de reporte: {{ $reportDate->isoFormat('D [de] MMMM [de] Y') }}
        </span>
    </div>-->
    </div>

    <div class="card-body">
        <div class="owner-info">
            <div><strong>Casa:</strong> {{ $house->address ?? $house->id }}</div>
            <div><strong>Propietario: </strong> {{ $house->owner->first()->name ?? 'Sin propietario' }}</div>
        </div>

        <table>
            <thead>
            <tr>
                <th>Fecha</th>
                <th>Concepto</th>
                <th style="text-align: right;">Cobro (S/)</th>
                <th style="text-align: right;">Pago (S/)</th>
                <th>Nro Transacción</th>
                <th style="text-align: right;">Balance (S/)</th>
            </tr>
            </thead>
            <tbody>
            @forelse($balanceItems as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}</td>
                    <td>{{ $item->concept }}</td>
                    <td style="text-align: right;">
                        {{ $item->charge_amount > 0 ? number_format($item->charge_amount, 2) : '' }}
                    </td>
                    <td style="text-align: right;">
                        {{ $item->payment_amount > 0 ? number_format($item->payment_amount, 2) : '' }}
                    </td>
                    <td>{{ $item->transaction_code }}</td>
                    <td style="text-align: right; font-weight: bold;">
                        {{ number_format($item->balance, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No hay movimientos para mostrar.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="summary">
            <table class="summary-table">
                <tbody>
                <!-- Fila para Cobros y Pagos -->
                <tr>
                    <!-- Celda de Total Cobros -->
                    <td style="width: 50%; padding: 10px; border: 1px solid #dee2e6; vertical-align: top;">
                        <div class="label">Total Cobros</div>
                        <div class="value negative">S/ {{ number_format($totals['charges'], 2) }}</div>
                    </td>
                    <!-- Celda de Total Pagos -->
                    <td style="width: 50%; padding: 10px; border: 1px solid #dee2e6; vertical-align: top;">
                        <div class="label">Total Pagos</div>
                        <div class="value positive">S/ {{ number_format($totals['payments'], 2) }}</div>
                    </td>
                </tr>
                <!-- Fila para Balance Final -->
                <tr>
                    <!-- Celda que ocupa las dos columnas -->
                    <td colspan="2" style="padding: 15px 10px; border: 1px solid #dee2e6; background-color: #e2f0ff;">
                        <!-- Usamos una tabla anidada para alinear label y value -->
                        <table style="width: 100%;">
                            <tr>
                                <td class="label" style="text-align: left;">Balance Final</td>
                                <td class="value {{ $totals['final_balance'] >= 0 ? 'negative' : 'positive' }}"
                                    style="text-align: right;">
                                    S/ {{ number_format($totals['final_balance'], 2) }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
            <!--            <div class="summary-grid">
                <div class="summary-item">
                    <div class="label">Total Cobros</div>
                    <div class="value negative">S/ {{ number_format($totals['charges'], 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="label">Total Pagos</div>
                    <div class="value positive">S/ {{ number_format($totals['payments'], 2) }}</div>
                </div>
            </div>
            <div class="summary-item" style="margin-top: 15px; background-color: #e2f0ff;">
                <div class="label">Balance Final</div>
                <div class="value {{ $totals['final_balance'] > 0 ? 'negative' : 'positive' }}">
                    S/ {{ number_format($totals['final_balance'], 2) }}
            </div>
        </div>-->
        </div>
    </div>
</div>
</body>
</html>
