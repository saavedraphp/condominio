<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Mantenimiento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .report-container {
            max-width: 800px;
            margin: auto;

            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .page-break {
            page-break-after: always;
        }

        .expense-title {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
            font-size: 14px;
        }

        .image-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .image-table td {
            width: 50%;
            height: 220px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
        }

        .image-table img {
            max-width: 90%;
            max-height: 200px;
        }

        .image-single {
            width: 100%;
            height: 250px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
        }

        .no-image {
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="report-container">
    @foreach($expenses_detail as $key => $expense)

        <div class="page-break">
            <div class="expense-title">
                #: {{ $key + 1 }} - {{ $expense->title }}
            </div>

            {{-- Primera fila: 2 imágenes --}}
            <table class="image-table">
                <tr>
                    <td>
                        <p>Recibo</p>
                        @if($expense->file_path_receipt)
                            <img src="{{ $expense->getImagePathReceipt($attributes['is_preview']) }}">
                        @else
                            <span class="no-image">No tiene imagen</span>
                        @endif
                    </td>

                    <td>
                        <p>Pago</p>
                        @if($expense->file_path)
                            <img src="{{ $expense->getImagePath($attributes['is_preview']) }}">
                        @else
                            <span class="no-image">No tiene imagen</span>
                        @endif
                    </td>
                </tr>
            </table>

            {{-- Segunda fila: imagen centrada --}}
            <table style="width:100%; margin-top:15px;">
                <tr>
                    <td class="image-single" style="height:220px; text-align:center; vertical-align:middle;">
                        <p>Trabajo</p>

                        @if($expense->file_path_job)
                            <img
                                src="{{ $expense->getImagePathJob($attributes['is_preview']) }}"
                                style="height:200px; width:auto; display:block; margin:0 auto;"
                            >
                        @else
                            <span class="no-image">No tiene imagen</span>
                        @endif

                    </td>
                </tr>
            </table>

        </div>

    @endforeach
</div>

</body>
