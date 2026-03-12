<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Balance</title>
    <style>
        /* Estilos generales inspirados en tu ejemplo y mejorados */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
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

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }

        /* Contenido principal en dos columnas */
        .main-content {
            width: 100%;
            margin-top: 15px;
            border-spacing: 20px 0;
            border-collapse: separate;
        }

        .main-content td {
            vertical-align: top;
            width: 50%;
        }

        /* Gráfico */
        .chart-container img {
            max-width: 100%;
            height: auto;
            border: 0 solid #eee;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .group-header {
            background-color: #333;
            color: white;
            padding: 10px;
            font-size: 1.2em;
            margin-top: 20px;
            margin-bottom: 1px;
        }

        .total-row td {
            font-weight: bold;
            background-color: #eaf1fb;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
        }

        .tabla_balance {
            width: 100%;
            border-collapse: collapse;

            .title {
                font-size: 16px;
                font-weight: bold;
                padding-bottom: 10px;
            }

            .value_neative {
                font-size: 16px;
                color: #dc3545;
                font-weight: bold;
                text-align: right;
            }

            .value_positive {
                font-size: 16px;
                color: #28a745;
                font-weight: bold;
                text-align: right;
            }

        }

        .signature-block {
            margin-top: 80px;
            padding: 20px;
        }

        .signature-block .line {
            border-top: 1px solid #333;
            width: 250px;
            margin: 0 auto 5px 0; /* Alinear a la izquierda */
        }

        .signature-block p {
            margin: 0;
            line-height: 1.4;
        }

        .signature-container {
            /* Define el espacio que quieres que ocupe la firma */
            width: 250px; /* Un buen tamaño para una firma, ajústalo según necesites */
            margin-top: 10px; /* Espacio por encima */
        }

        .signature-container img {
            /* La magia sucede aquí */
            max-width: 100%; /* La imagen nunca será más ancha que su contenedor */
            height: auto; /* La altura se ajusta para mantener la proporción */
            display: block; /* Evita espacios extra debajo de la imagen */
        }

        .tabla_logo td,
        .tabla_logo th {
            border: none;
        }
    </style>
</head>
<body>

<div class="report-container">
    <div class="card-header">
        <table class="tabla_logo">
            <tr>
                <td><img src="{{ $attributes['logo_path'] }}" alt="Logo"></td>
                <td style="vertical-align: top">
                    <h2 style="margin-bottom: 5px;">{{$attributes['site_name']}}</h2>
                    <p style="margin-top: 0; font-size: 1.5em; font-weight: bold; line-height: 1.2;">
                        KM32 Panamericana Sur <br>
                        La Franja - Condomino Isla de San Pedro
                    </p>
                </td>
                <td>
                    @if($attributes['is_preview'] && !$attributes['with_images'])
                        <a href="{{ route('admin.reports.income-statement.pdf', [
            'start_date' => $attributes['start_date'],
            'end_date' => $attributes['end_date'],
            'with_images' => $attributes['with_images'],
            ])
            }}" class="download-button">Descargar PDF
                        </a>
                    @endif
                </td>
            </tr>
        </table>
        <div style="padding: 20px; text-align: center;">
            <h1>REPORTE FINANCIERO</h1>
        </div>
        <p>Generado el: {{ date('d/m/Y H:i') }}</p>
        <p>Período: {{ \Carbon\Carbon::parse($attributes['start_date'])->format('d/m/Y') }}
            - {{  \Carbon\Carbon::parse($attributes['end_date'])->format('d/m/Y') }}</p>
    </div>
    <h2 class="group-header">INGRESOS: S/ {{number_format($summary['total_incomes'], 2) }}</h2>
    <table>
        <thead>
        <tr>
            {{-- Cambia estos encabezados por los de tu modelo --}}
            <th>Fecha</th>
            <th>Dirección</th>
            <th>Código de transacción</th>
            <th style="text-align: right;">Monto</th>
        </tr>
        </thead>
        <tbody>
        @foreach($payments_detail as $payment)
            <tr>
                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                <td>{{ substr($payment->house->address,0,30) ?? 'Sin dirección' }}</td>
                <td>{{ $payment->transaction_code ?? 'Sin Código' }}</td>
                <td style="text-align: right;">S/ {{ number_format($payment->amount, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <br>
    <h2 class="group-header">EGRESOS: S/ {{number_format($summary['total_expenses'], 2) }}</h2>
    <table>
        <thead>
        <tr>
            {{-- Cambia estos encabezados por los de tu modelo --}}
            <th>#</th>
            <th>Fecha</th>
            <th>Titulo</th>
            <th>Tipo Gasto</th>
            <th style="text-align: right;">Monto</th>
        </tr>
        </thead>
        <tbody>
        @foreach($expenses_detail as $key => $expense)
            <tr>
                <td>{{$key +1}}</td>
                <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}</td>
                <td>{{ substr($expense->title,0,30) ?? 'Sin titulo' }}</td>
                <td>{{$expense->annualBudget?->budgetType?->name}}</td>
                <td style="text-align: right;">S/ {{ number_format($expense->amount, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="signature-block">
    <table class="main-content">
        <tr>
            <td>
                <br>
                <div class="section-title">{{$attributes['chart_description']}}</div>
                <div class="chart-container">
                    <!-- Ruta absoluta para la imagen del gráfico -->
                    <img src="{{ $attributes['tablaImagePath'] }}" alt="Gráfico de Cuota">
                </div>
            </td>
            <td style="margin-top: 0">
                <h1>RESUMEN:</h1>
                <table class="tabla_balance">
                    <tr>
                        <td class="title">Ingresos:</td>
                        <td class="value_positive">S/ {{number_format($summary['total_incomes'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="title">Egresos:</td>
                        <td class="value_neative">S/ {{number_format($summary['total_expenses'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="title">Resultado Neto:</td>
                        <td class="value_positive">S/ {{number_format($summary['balance'], 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div class="signature-block">
        @if($attributes['signature_path'])
            <div class="signature-container">
                <img src="{{$attributes['signature_path']}}" alt="Firma digital">
            </div>
        @endif
        <div class="line"></div>
        <p><strong>{{$attributes['name_president']}}</strong></p>
        <p><strong>Presidente de Directiva Provisional</strong></p>
        <p>{{$attributes['site_name']}}</p>
    </div>
    <br>
</div>
</body>
</html>
