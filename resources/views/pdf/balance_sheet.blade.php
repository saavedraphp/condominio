<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Casas</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 4px 6px;
        }

        .header-box {
            border: 2px solid #000;
            background: #d9e1e5;
            text-align: center;
            font-weight: bold;
            padding: 6px;
            margin-top: 10px;
        }

        .section-title {
            font-weight: bold;
            padding-top: 10px;
        }

        .right {
            text-align: right;
        }

        .indent {
            padding-left: 25px;
        }

        .total {
            font-weight: bold;
        }

        .balance {
            font-weight: bold;
        }

        .double-line {
            border-top: 2px solid black;
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
    </style>
</head>
<body>

<div class="container">
    <div class="card-header">
        <img src="{{ $attributes['logo_path'] }}" alt="Logo">
        @if( $attributes['is_preview'])
            <a href="{{ route('admin.reports.balance-sheet.pdf', [
            'anho' => $attributes['anho'],
            'month' => $attributes['month'],
            ])
            }}" class="download-button">Descargar PDF
            </a>
        @endif
        <h1>BALANCE</h1>
        <h2>CORRESPONDIENTE AL {{  $attributes['last_day_month']}} DE {{  $attributes['month_name']}}
            - {{ $attributes['anho']}}</h2>
        <h2>{{$attributes['site_name']}}</h2>
        <p>Generado el: {{ date('d/m/Y H:i') }}</p>
    </div>
    <div class="header-box">
        INFORME DE INGRESOS Y EGRESOS
    </div>
    <table>

        <tr>
            <td colspan="2" class="section-title">
                INGRESOS AL {{$attributes['month_name']}} DE {{$attributes['anho']}}
            </td>
        </tr>

        <tr>
            <td class="indent">SALDO DEL MES ANTERIOR</td>
            <td class="right">{{number_format($last_balance,2)}}</td>
        </tr>

        <tr>
            <td class="indent">INGRESOS DEL MES DE {{$attributes['month_name']}} {{$attributes['anho']}}</td>
            <td class="right">{{number_format($current_total_incomes,2)}}</td>
        </tr>

        <tr>
            <td class="indent">CUOTA DE GASTOS COMUNES (17 UNIDADES)</td>
            <td class="right">{{number_format($incomes_general['common_income'],2)}}</td>
        </tr>

        <tr>
            <td class="indent">CUOTA EXTRAORDINARIA</td>
            <td class="right">{{number_format($incomes_general['extraordinary_income'],2)}}</td>
        </tr>

        <tr>
            <td class="indent">INGRESOS DE RENTA DE PARRILLA</td>
            <td class="right">{{number_format($incomes_general['grill_rental_income'],2)}}</td>
        </tr>

        <tr>
            <td class="indent">INGRESOS DE RENTA DE CINE</td>
            <td class="right">{{number_format($incomes_general['cine_rental_income'],2)}}</td>
        </tr>

        <tr>
            <td class="indent">PENALIDAD POR MORA DEL MES</td>
            <td class="right">{{number_format($incomes_general['penalties_income'],2)}}</td>
        </tr>

        <tr class="total">
            <td>TOTAL INGRESOS AL {{$attributes['month_name']}} DE {{$attributes['anho']}}</td>
            <td class="right">{{number_format($grandTotalIncome,2)}}</td>
        </tr>

    </table>


    <table>

        <tr>
            <td colspan="2" class="section-title">
                EGRESOS AL {{$attributes['month_name']}} DE {{$attributes['anho']}}
            </td>
        </tr>
        @foreach($expenses as $expense)
            <tr>
                <td class="indent">{{$expense['name']}}</td>
                <td class="right">{{number_format($expense['total'], 2)}} </td>
            </tr>
        @endforeach


        <tr class="total">
            <td>TOTAL EGRESOS AL {{$attributes['month_name']}} DE {{$attributes['anho']}}</td>
            <td class="right">{{number_format($grand_total_expenses,2)}}</td>
        </tr>

        <tr class="balance">
            <td>BALANCE</td>
            <td class="right">({{number_format($balance,2)}})</td>
        </tr>

    </table>
<p style="page-break-before: always;"></p>

    <div class="header-box">
        INFORME DE ACTIVOS Y PASIVOS
    </div>

    <table>

        <tr>
            <td colspan="2" class="section-title">ACTIVOS</td>
        </tr>

        <tr>
            <td colspan="2" class="section-title">ACTIVOS CORRIENTES</td>
        </tr>

        <tr>
            <td class="indent">EFECTIVO EN BANCA (BCP)</td>
            <td class="right">{{number_format($current_assets['cash_bank'],2)}}</td>
        </tr>

        <tr>
            <td class="indent">CUENTAS POR COBRAR</td>
            <td class="right">{{number_format($current_assets['accounts_receivable'],2)}}</td>
        </tr>

        <tr>
            <td class="indent">GASTOS ANTICIPADOS</td>
            <td class="right">{{number_format($current_assets['expenses_prepaid'],2)}}</td>
        </tr>

        <tr class="total">
            <td>TOTAL ACTIVOS CORRIENTES</td>
            <td class="right">{{number_format(array_sum($current_assets),2)}}</td>
        </tr>

        <tr>
            <td colspan="2" class="section-title">ACTIVOS NO CORRIENTES</td>
        </tr>

        <tr>
            <td class="indent">ACTIVOS GENERALES</td>
            <td class="right">{{number_format($non_current_assets['assets'],2)}}</td>
        </tr>

        <tr>
            <td class="indent">ACTIVOS - SUMINISTROS</td>
            <td class="right">{{number_format($non_current_assets['supplies'],2)}}</td>
        </tr>

        <tr class="total">
            <td>TOTAL ACTIVOS NO CORRIENTES</td>
            <td class="right">{{number_format(array_sum($non_current_assets),2)}}</td>
        </tr>

        <tr class="total">
            <td>TOTAL ACTIVOS</td>
            <td class="right">{{number_format($total_assets,2)}}</td>
        </tr>

        <tr>
            <td colspan="2" class="section-title">PASIVOS</td>
        </tr>

        <tr>
            <td class="indent">REPARACIONES PENDIENTES</td>
            <td class="right">{{number_format($liabilities['pending_repairs'],2)}}</td>
        </tr>

        <tr>
            <td class="indent">DEUDAS POR PAGAR</td>
            <td class="right">{{number_format($liabilities['debts_payable'],2)}}</td>
        </tr>

        <tr>
            <td class="indent">ADELANTO DE CUOTAS</td>
            <td class="right">{{number_format($liabilities['advances_payments'],2)}}</td>
        </tr>

        <tr class="total">
            <td>TOTAL PASIVOS</td>
            <td class="right">{{number_format(array_sum($liabilities),2)}}</td>
        </tr>

        <tr class="double-line total">
            <td>BALANCE PATRIMONIAL</td>
            <td class="right">{{number_format($equity_balance,2)}}</td>
        </tr>

    </table>
</div>

</body>
</html>
