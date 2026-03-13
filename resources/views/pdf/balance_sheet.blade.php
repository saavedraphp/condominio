<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Balance Financiero</title>
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

        .td-right {
            text-align: right;
            width: 20%;
        }

        .header-box {
            border: 2px solid #000;
            background: #d9e1e5;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            padding: 6px;
            margin-top: 10px;
        }

        .section-title {
            font-weight: bold;
            padding-top: 10px;
        }

        .assets-title {
            font-weight: bold;
            padding-top: 10px;
            text-decoration: underline;
        }
        .right {
            text-align: right;
        }

        .indent {
            padding-left: 25px;
        }

        .indent-two {
            padding-left: 50px;
        }

        .indent-three {
            padding-left: 75px;
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

        .new_page {
            page-break-before: always;
        }
    </style>
</head>
<body>

<div>
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
                INGRESOS AL {{  $attributes['last_day_month']}} DE {{$attributes['month_name']}}  {{$attributes['anho']}}
            </td>
        </tr>

        <tr>
            <td class="indent">SALDO DEL MES ANTERIOR</td>
            <td class="td-right">{{number_format($last_balance,2)}}</td>
        </tr>

        <tr>
            <td class="indent">INGRESOS DEL MES DE {{$attributes['month_name']}} {{$attributes['anho']}}</td>
            <td class="right">{{number_format($current_total_incomes,2)}}</td>
        </tr>

        <tr>
            <td class="indent-two">CUOTA DE GASTOS COMUNES (17 UNIDADES)</td>
            <td class="right">{{number_format($incomes_general['common_income'],2)}}</td>
        </tr>

        <tr>
            <td class="indent-two">CUOTA EXTRAORDINARIA</td>
            <td class="right">{{number_format($incomes_general['extraordinary_income'],2)}}</td>
        </tr>

        <tr>
            <td class="indent-two">INGRESOS DE RENTA DE PARRILLA</td>
            <td class="right">{{number_format($incomes_general['grill_rental_income'],2)}}</td>
        </tr>

        <tr>
            <td class="indent-two">INGRESOS DE RENTA DE CINE</td>
            <td class="right">{{number_format($incomes_general['cine_rental_income'],2)}}</td>
        </tr>

        <tr>
            <td class="indent-two">PENALIDAD POR MORA DEL MES</td>
            <td class="right">{{number_format($incomes_general['penalties_income'],2)}}</td>
        </tr>

        <tr class="total">
            <td class="right">TOTAL INGRESOS AL {{  $attributes['last_day_month']}} DE {{$attributes['month_name']}}  {{$attributes['anho']}}</td>
            <td class="right">{{number_format($grandTotalIncome,2)}}</td>
        </tr>

    </table>


    <table>

        <tr>
            <td colspan="2" class="section-title">
                EGRESOS AL {{  $attributes['last_day_month']}} DE {{$attributes['month_name']}}  {{$attributes['anho']}}
            </td>
        </tr>
        @foreach($expenses as $expense)
            <tr>
                <td class="indent">{{$expense['name']}}</td>
                <td class="right">{{number_format($expense['total'], 2)}} </td>
            </tr>
        @endforeach


        <tr class="total">
            <td class="right">TOTAL EGRESOS AL {{  $attributes['last_day_month']}} DE {{$attributes['month_name']}}  {{$attributes['anho']}}</td>
            <td class="td-right">{{number_format($grand_total_expenses,2)}}</td>
        </tr>

        <tr class="balance">
            <td class="right">BALANCE</td>
            <td class="td-right">{{$balance_formated}}</td>
        </tr>

    </table>
    <div class="header-box new_page">
        INFORME DE ACTIVOS Y PASIVOS
    </div>

    <table>

        <tr>
            <td colspan="2" class="section-title">ACTIVOS</td>
        </tr>

        <tr>
            <td colspan="2" class="assets-title indent ">ACTIVOS CORRIENTES</td>
        </tr>

        <tr>
            <td class="indent-two">EFECTIVO EN BANCA (BCP)</td>
            <td class="td-right right">{{number_format($current_assets['cash_bank'],2)}}</td>
        </tr>

        <tr>
            <td class="indent-two">CUENTAS POR COBRAR</td>
            <td class="right">{{number_format($current_assets['accounts_receivable'],2)}}</td>
        </tr>

        <tr>
            <td class="indent-two">GASTOS ANTICIPADOS</td>
            <td class="right">{{number_format($current_assets['expenses_prepaid'],2)}}</td>
        </tr>

        <tr class="total">
            <td class="right">TOTAL ACTIVOS CORRIENTES</td>
            <td class="right">{{number_format(array_sum($current_assets),2)}}</td>
        </tr>

        <tr>
            <td colspan="2" class="assets-title indent">ACTIVOS NO CORRIENTES</td>
        </tr>

        <tr>
            <td class="indent-two">ACTIVOS GENERALES</td>
            <td class="right">{{number_format($non_current_assets['assets'],2)}}</td>
        </tr>

        <tr>
            <td class="indent-two">ACTIVOS - SUMINISTROS</td>
            <td class="right">{{number_format($non_current_assets['supplies'],2)}}</td>
        </tr>

        <tr class="total">
            <td class="right">TOTAL ACTIVOS NO CORRIENTES</td>
            <td class="td-right right">{{number_format(array_sum($non_current_assets),2)}}</td>
        </tr>

        <tr class="total">
            <td class="right">TOTAL ACTIVOS</td>
            <td class="td-right right">{{number_format($total_assets,2)}}</td>
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
            <td class="right">TOTAL PASIVOS</td>
            <td class="right">{{number_format(array_sum($liabilities),2)}}</td>
        </tr>

        <tr class="double-line total">
            <td class="right">BALANCE PATRIMONIAL</td>
            <td class="right">{{number_format($equity_balance,2)}}</td>
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
</div>

</body>
</html>
