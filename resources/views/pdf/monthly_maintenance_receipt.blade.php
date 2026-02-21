<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Mantenimiento</title>
    <style>
        /* Estilos generales del documento */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Contenedor principal */
        .container {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            padding: 10px 20px;
        }

        /* Cabecera */
        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header img.logo {
            max-width: 120px;
            margin-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 16px;
            font-weight: normal;
        }

        .header h3 {
            margin: 10px 0 0 0;
            font-size: 14px;
            font-weight: bold;
            color: #d9534f; /* Un tono rojo para destacar */
        }

        hr {
            border: 0;
            border-top: 1px solid #ccc;
            margin: 20px 0;
        }

        /* Información del asociado */
        .info-asociado p {
            margin: 4px 0;
            font-size: 14px;
        }

        .info-asociado strong {
            display: inline-block;
            width: 90px;
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

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }

        /* Desglose de cobro */
        .desglose-table {
            width: 100%;
        }

        .desglose-table td {
            padding: 0 0;
            font-size: 13px;
        }

        .desglose-table td.item {
            text-align: left;
        }

        .desglose-table td.amount {
            text-align: right;
        }

        .desglose-table tr.total td {
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 8px;
            font-size: 14px;
        }

        /* Gráfico */
        .chart-container img {
            max-width: 100%;
            height: auto;
            border: 0 solid #eee;
        }

        .img-consumption {
            max-width: 100%;
            max-height: 200px;
            border: 0 solid #eee;
        }

        .img-consumption p {
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }

        /* Historial de lecturas */
        .historial-container {
            margin-top: 15px;
        }

        .historial-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .historial-table th, .historial-table td {
            border: 1px solid #999;
            padding: 3px;
            text-align: left;
        }

        /* Resumen de cobros */
        .summary-table {
            width: 100%;
            margin-top: 0;
            font-size: 14px;
        }

        .summary-table td {
            padding: 0;
            width: 33%;
        }

        .summary-table .label {
            font-weight: bold;
        }

        .summary-table .value {
            text-align: left;
            font-weight: bold;
        }

        /* Información de pago */
        .payment-info {
            margin-top: 10px;
            padding: 0;
            border: 1px dashed #999;
            text-align: center;
            background-color: #f9f9f9;
        }

        .payment-info p {
            margin: 0;
            font-size: 13px;
        }

        .payment-info strong {
            font-size: 14px;
        }

    </style>
</head>
<body>

<div class="container">
    <header class="header">
        <!-- Usamos public_path() para obtener la ruta absoluta del servidor -->
        <img src="{{ $logoPath }}" alt="Logo" class="logo">
        <h1>{{$title_details_line_1}}</h1>
        <h2>{!!$title_details_line_2!!}</h2>
        @if(!$is_type_house_board)
            <h2>{{$ruc_assoc_prop_isp}}</h2>
        @endif
        <h3>Pagar antes de {{$period_month}} 15, {{$period_year}}</h3>
    </header>

    <p>
        Estimado(a), {{$associated['name']}}, enviar sus baucher, consultas e inquietudes al
        siguiente correo: <a href="mailto:$contact_email">{{$contact_email}}</a>
    </p>

    <div class="info-asociado">
        <p><strong>Asociado:</strong> {{$associated['name']}}</p>
        <p><strong>Propiedad:</strong> {{$associated['property']}}</p>
    </div>
    @php $total  = 0 @endphp
    @if(!$is_type_house_board)
        <table class="main-content">
            <tr>
                <td>
                    <div class="section-title">{{$chart_description}}</div>
                    <div class="chart-container">
                        <!-- Ruta absoluta para la imagen del gráfico -->
                        <img src="{{ $tablaImagePath }}" alt="Gráfico de Cuota">
                    </div>
                </td>
                <td>
                    <div class="section-title">{{$details_payment}}</div>
                    <table class="desglose-table">

                        @foreach($details as $item)
                            <tr>
                                <td class="item">S/ {{number_format($item['amount'], 2) }} - {{ $item['title'] }}</td>
                            </tr>
                            @php $total += $item['amount'] @endphp
                        @endforeach

                        <tr class="total">
                            <td class="item">S/ {{number_format($total, 2)}} - TOTAL</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        @if($show_table_energy)
            <div class="historial-container">
                <div class="section-title">Historial: Lecturas en KWh de su medidor de Luz</div>
                <table class="historial-table">
                    <tbody>
                    @foreach ($electrical_history_table as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>
                                    {{-- Aseguramos que la celda tiene datos --}}
                                    @if (isset($cell['title']))
                                        {{-- Modificamos un poco el 'title' para que coincida con tu formato "Mes Dia, Año" --}}
                                        {{-- Reemplazamos el primer espacio por " 15, " --}}
                                        {{ preg_replace('/ /', ' 15, ', $cell['title'], 1) }} =

                                        {{-- Imprimimos el consumo si no es "N/A" o null --}}
                                        @if (isset($cell['consumption']) && $cell['consumption'] !== 'N/A')
                                            {{ $cell['consumption'] }}
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
    <table class="summary-table">
        <tr>
            <td class="label">Deuda Anterior</td>
            <td class="label">Cobro del Mes Actual</td>
            <td class="label">Total Adeudado</td>
        </tr>
        <tr>
            <td class="value">{{$debt}}</td>
            <td class="value">S/{{number_format($total, 2)}}</td>
            <td class="value">S/{{number_format($total_debt, 2)}}</td>
        </tr>
    </table>

    <div class="payment-info">
        <table width="100%">
            <tr>
                <td>
                    <p><strong>Pagar el monto de S/{{number_format($total_debt, 2)}} antes de {{$period_month}}
                            15, {{$period_year}} en
                            la cuenta
                            aprobada:</strong></p>
                    <p>A Nombre de {{$bank_account_name}}</p>
                    <p><strong>{{$bank_name}}</strong></p>
                    <p><strong>Ahorros</strong> {{$bank_account}}</p>
                    <p><strong>CCI</strong> {{$bank_account_cci}}</p>
                </td>

                @if($show_table_energy)
                    @if(!empty($image_consumption))
                        <td class="img-consumption">
                            <p>Imagen de lectura</p>
                            <img src="{{ $image_consumption }}" alt="Gráfico de Consumo" style="max-height: 120px">
                        </td>
                    @else
                        <td class="img-consumption">
                            <p style="color: red">No se ha ingresado el gráfico del consumo.</p>
                        </td>
                    @endif
                @endif
            </tr>
        </table>
    </div>
</div>

</body>
</html>
