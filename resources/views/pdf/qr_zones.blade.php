<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Pase de Visita</title>
    <style>
        /* Estilos generales del cuerpo del documento */
        body { font-family: sans-serif; color: #333; }

        /* Tabla principal que actúa como el contenedor principal */
        .container-table {
            width: 100%;
            max-width: 600px; /* Ancho máximo para el pase */
            margin: 40px auto;
            border: 2px solid #007BFF;
            border-radius: 10px; /* Nota: border-radius puede no funcionar en todas las versiones de dompdf */
            border-collapse: separate; /* Necesario para que el border-radius tenga efecto */
            border-spacing: 0;
            padding: 0;
        }

        /* Título principal */
        h1 {
            color: #0056b3;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
        }

        /* Tabla anidada para los detalles, asegura una alineación perfecta */
        .details-table {
            width: 100%;
            text-align: left;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .details-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .details-table td.label {
            width: 130px; /* Ancho fijo para la columna de etiquetas */
            font-weight: bold;
        }

        /* Celda que contiene el código QR, centrada */
        .qr-cell {
            text-align: center;
            padding: 20px 0;
        }

        /* Celda que contiene el código de acceso, centrada */
        .code-cell {
            text-align: center;
            padding-bottom: 25px; /* Espaciado inferior */
        }

        /* Span para estilizar el código de acceso, idéntico al original */
        .access-code {
            font-family: monospace;
            font-size: 1.5em;
            letter-spacing: 3px;
            background-color: #f0f0f0;
            padding: 10px 15px;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>

<table class="container-table" cellpadding="0" cellspacing="0">
    <tr>
        <td style="padding: 25px; text-align: center;">
            <!-- Título -->
            <h1>Codigo QR</h1>

            <!-- Tabla de Detalles (Anidada) -->
            <table class="details-table">
                <tr>
                    <td class="label">Título:</td>
                    <td>{{ $data->title }}</td>
                </tr>
                <tr>
                    <td class="label">Detalle:</td>
                    <td>{{ $data->description }}</td>
                </tr>
                <tr>
                    <td class="label">Código:</td>
                    <td>{{ $data->code }}</td>
                </tr>
            </table>

            <!-- Celda para el Código QR -->
            <div class="qr-cell">
                <img src="data:image/png;base64,{{ $qrCode }}" alt="Código QR">
            </div>

            <!-- Celda para el Código de Acceso -->
            <div class="code-cell" style="display: none">
                <span class="access-code">{{ $data->code }}</span>
            </div>
        </td>
    </tr>
</table>

</body>
</html>
