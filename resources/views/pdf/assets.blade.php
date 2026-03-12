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

        .subtotal {
            font-weight: bold;
            background-color: #f0f0f0;
            align-items: end;
            td {
                text-align: right;
            }
        }


        .total {
            font-weight: bold;
            font-size: large;
            background-color: #f0f0f0;
            align-items: end;
            td {
                text-align: right;
            }
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
            text-align: left;
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
            <a href="{{ route('admin.reports.balance-sheet.pdf')
            }}" class="download-button">Descargar PDF
            </a>
        @endif
        <h1>ACTIVOS Y SUMINISTROS</h1>
        <h2>{{$attributes['site_name']}}</h2>
        <p>Generado el: {{ date('d/m/Y H:i') }}</p>
    </div>


    @foreach($data as $year => $reporteAnio)
        <div class="header-box">
            {{ $year }} ACTIVOS - POMPEYA
        </div>
    <p></p>
        <table>
            <thead>
            <tr>
                <th>Código</th>
                <th>Adquicisión</th>
                <th>Título</th>
                <th>Marca/Modelo</th>
                <th class="right">Monto</th>
                <th class="right">Valor en Perú</th>
            </thead>
            <tbody>
            <!-- 1. ITERAR ACTIVOS PRINCIPALES -->
            @foreach($reporteAnio['assets']['items'] as $activo)
                <tr>
                    <td>{{ $activo['asset_code'] }}</td>
                    <td>{{ $activo['title'] }}</td>
                    <td>{{ $activo['title'] }}</td>
                    <td>{{ $activo['asset_brand'] }}</td>
                    <td class="right">{{ $activo['amount'] }}</td>
                    <td class="right">{{ $activo['market_value'] }}</td>

                </tr>
            @endforeach
            <!-- SUBTOTAL assets -->
            <tr class="subtotal">
                <td colspan="4">SUBTOTAL</td>
                <td>{{ number_format($reporteAnio['assets']['subtotal_amount'], 2) }}</td>
                <td>{{ number_format($reporteAnio['assets']['subtotal_marker_value'], 2) }}</td>
            </tr>

            <!-- 2. ITERAR SUMINISTROS -->
            <tr><td colspan="6">Suministros</td></tr>
            @foreach($reporteAnio['supplies']['items'] as $suministro)
                <tr>
                    <td>{{ $suministro['asset_code'] }}</td>
                    <td>{{ $suministro['title'] }}</td>
                    <td>{{ $suministro['title'] }}</td>
                    <td>{{ $suministro['asset_brand'] }}</td>
                    <td class="right">{{ $suministro['amount'] }}</td>
                    <td class="right">{{ $suministro['market_value'] }}</td>

                </tr>
            @endforeach
            <!-- SUBTOTAL suplies -->
            <tr class="subtotal">
                <td colspan="4">SUBTOTAL</td>
                <td>{{ number_format($reporteAnio['supplies']['subtotal_amount'], 2) }}</td>
                <td>{{ number_format($reporteAnio['supplies']['subtotal_marker_value'], 2) }}</td>
            </tr>

            <!-- 3. TOTAL GENERAL DEL AÑO -->
            <tr class="total">
                <td colspan="4">TOTAL</td>
                <td>{{ number_format($reporteAnio['sub_total_anho']['total_amount'], 2) }}</td>
                <td>{{ number_format($reporteAnio['sub_total_anho']['total_marker_value'], 2) }}</td>
            </tr>

            </tbody>
        </table>

    @endforeach


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
