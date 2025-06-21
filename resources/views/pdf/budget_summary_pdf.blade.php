<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report_title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 5px 0; font-size: 14px; color: #337ab7; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; background-color: #f9f9f9; }
        .summary-box { border: 1px solid #008080; padding: 10px; margin-top: 30px; }
        .summary-box table { border: none; }
        .summary-box td { border: none; padding: 4px 0; }
        .percentage { font-weight: bold; }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $report_owner }}</h1>
    <h2>{{ $report_title }}</h2>
</div>

<table>
    <thead>
    <tr>
        <th>Descripcion</th>
        <th class="text-right">Cantidad ({{ $currency_symbol }})</th>
        <th class="text-right">Porcentaje</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($items as $item)
        <tr>
            <td colspan="3" style="font-weight:bold; background-color: #e9ecef;">{{ $item['budget_type_name'] }}</td>
        </tr>
        <tr>
            <td>Presupuesto Anual de {{ $item['description'] }}:<br>
                Gastos de {{ $item['description'] }} a {{ $month_label }} {{ $year }}:
            </td>
            <td class="text-right">
                {{ number_format($item['budgeted_amount'], 2) }}<br>
                {{ number_format($item['spent_amount'], 2) }}
            </td>
            <td class="text-right percentage">
                <br> <!-- Para alinear con la segunda línea de montos -->
                {{ number_format($item['percentage_spent'], 2) }}%
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="3" style="text-align:center;">No hay datos de presupuesto para este período.</td>
        </tr>
    @endforelse
    </tbody>
</table>

@if (count($items) > 0)
    <div class="summary-box">
        <table>
            <tr>
                <td>Presupuesto Total ({{ $year }}):</td>
                <td class="text-right">{{ $currency_symbol }} {{ number_format($totals['total_budgeted'], 2) }}</td>
                <td></td> <!-- Columna vacía para alinear con porcentaje -->
            </tr>
            <tr>
                <td>Gastos realizados ({{ $year }} {{ $month_label }}):</td>
                <td class="text-right">{{ $currency_symbol }} {{ number_format($totals['total_spent'], 2) }}</td>
                <td class="text-right percentage">{{ number_format($totals['overall_percentage_spent'], 2) }}%</td>
            </tr>
        </table>
    </div>
@endif

</body>
</html>
