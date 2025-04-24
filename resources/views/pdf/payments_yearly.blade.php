<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobantes del Año {{ $year }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top; /* Alinear contenido arriba */
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        td.amount {
            text-align: right;
            white-space: nowrap; /* Evitar que el monto se divida */
        }
        .payment-image {
            max-width: 80px; /* Limitar tamaño de imagen */
            max-height: 80px;
            display: block; /* Para que max-width funcione bien */
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 9px;
            color: #777;
        }
        /* Ocultar la imagen si no existe la ruta */
        img[src=""], img:not([src]) {
            display: none;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Resumen de Pagos - Año {{ $year }}</h1>
    @isset($userName)
        <p>Usuario: {{ $userName }}</p>
    @endisset
</div>

@if($payments->isEmpty())
    <p>No se encontraron pagos registrados para el año {{ $year }}.</p>
@else
    <table>
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Descripción / Título</th>
            <th class="amount">Monto Pagado</th>
            <th>Imagen</th> <!-- Columna para la imagen -->
        </tr>
        </thead>
        <tbody>
        @php $totalAmount = 0; @endphp
        @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                <td>{{ $payment->description ?? 'N/A' }}</td>
                <td class="amount">S/ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                <td>
                    {{-- Manejo de Imagen --}}
                    @if(!empty($payment->file_path) && Storage::disk(config('filesystems.default_image_disk', 'public'))->exists($payment->file_path))
                        {{-- Opción 1: Si la imagen está en el disco 'public' y linkeada simbólicamente --}}
                        {{-- <img src="{{ asset(Storage::url($payment->file_path)) }}" alt="Imagen Pago" class="payment-image"> --}}

                        {{-- Opción 2: Usar ruta absoluta (DOMPDF necesita acceso directo) --}}
                        {{-- ¡Asegúrate que la ruta sea accesible por el proceso del servidor web! --}}
                        <img src="{{ Storage::disk(config('filesystems.default_image_disk', 'public'))->path($payment->file_path) }}" alt="Imagen Pago" class="payment-image">

                        {{-- Opción 3: Base64 (Aumenta tamaño del PDF) --}}
                        {{-- @php
                            try {
                                $imageData = base64_encode(Storage::disk(config('filesystems.default_image_disk', 'public'))->get($payment->file_path));
                                $imageMime = Storage::disk(config('filesystems.default_image_disk', 'public'))->mimeType($payment->file_path);
                            } catch (\Exception $e) { $imageData = null; }
                        @endphp
                        @if($imageData)
                            <img src="data:{{ $imageMime }};base64,{{ $imageData }}" alt="Imagen Pago" class="payment-image">
                        @else
                            (Error al cargar imagen)
                        @endif --}}
                    @else
                        (Sin imagen)
                    @endif
                </td>
            </tr>
            @php $totalAmount += $payment->amount; @endphp
        @endforeach
        {{-- Fila de Total (Opcional) --}}
        <tr>
            <td colspan="2" style="text-align: right; font-weight: bold;">Total Año {{ $year }}:</td>
            <td class="amount" style="font-weight: bold;">S/ {{ number_format($totalAmount, 2, ',', '.') }}</td>
            <td></td> {{-- Celda vacía para columna imagen --}}
        </tr>
        </tbody>
    </table>
@endif

<div class="footer">
    Generado el: {{ $generationDate ?? date('d/m/Y H:i') }}
</div>

</body>
</html>
