<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Casas</title>
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
    </style>
</head>
<body>

<div class="report-container">
    <div class="card-header">
        <img src="{{ $attributes['logo_path'] }}" alt="Logo">
        @if($isPdf)
            <a href="{{ route('admin.reports.expenses.pdf', [
            'start_date' => $attributes['start_date'],
            'end_date' => $attributes['end_date'],
            'types' => $attributes['types'],
            ])
            }}" class="download-button">Descargar PDF
            </a>
        @endif
        <h1>Bitacora de Gastos</h1>
        <h2>{{$attributes['site_name']}}</h2>
        <p>Generado el: {{ now()->format('d/m/Y H:i') }}</p>
        <p>Período: {{ \Carbon\Carbon::parse($attributes['start_date'])->format('d/m/Y') }}
            - {{ \Carbon\Carbon::parse($attributes['end_date'])->format('d/m/Y') }}</p>

        @foreach($details_total as $detail => $itemDetail)
            <h3>{{$itemDetail['title']}}: {{number_format($itemDetail['amount'],2)}}</h3>
        @endforeach

        <h1>Total: {{ number_format($totals['total_amount'],2)}}</h1>
    </div>

    @forelse($reportData as $monthYear => $data)
        @php
            // Asegúrate que tu locale esté en español en config/app.php ('locale' => 'es')
            // para que los nombres de los meses salgan en español.
            $date = \Carbon\Carbon::createFromFormat('Y-m', $monthYear);
            $monthName = ucfirst($date->translatedFormat('F'));
            $year = $date->format('Y');
        @endphp

        <h2 class="group-header">{{ $monthName }} {{ $year }}</h2>

        <table>
            <thead>
            <tr>
                {{-- Cambia estos encabezados por los de tu modelo --}}
                <th>Fecha</th>
                <th>Título</th>
                <th>Tipo</th>
                <th style="text-align: right;">Monto</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data['items'] as $payment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($payment->date)->format('d/m/Y') }}</td>
                    <td>{{ \Illuminate\Support\Str::limit(($payment->title?? 'No disponible'),40,'...') }}</td>
                    <td>{{ $payment->type ?? 'N/A' }}</td>
                    <td style="text-align: right;">S/ {{ number_format($payment->amount, 2) }}</td>
                </tr>

            @endforeach
            @if($data['totalsByType']->isNotEmpty())
                <tr class="total-row-by-type">
                    <td colspan="4" style="text-align: center; font-weight: bold; padding: 8px 0;">
                        @foreach($data['totalsByType'] as $type => $total)
                            <span style="margin: 0 15px;">
                                {{ \Illuminate\Support\Str::title(strtolower($type)) }}: S/ {{ number_format($total, 2) }}
                            </span>
                        @endforeach
                    </td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">Total del Mes:</td>
                <td style="text-align: right;">S/ {{ number_format($data['total'], 2) }}</td>
            </tr>
            </tbody>
        </table>

    @empty
        <p class="no-data">No se encontraron registros para los filtros seleccionados.</p>
    @endforelse
</div>

</body>
</html>
