<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carta de Cobranza</title>
    <style>
        /* Estilos específicos para la carta, para no interferir con el recibo */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }

        .letter-container {
            padding: 20px 40px; /* Más padding lateral para formato de carta */
        }

        .letter-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .letter-header img {
            max-width: 120px;
            margin-bottom: 15px;
        }

        .letter-header h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .info-block {
            margin-bottom: 30px;
        }

        .info-block p {
            margin: 2px 0;
        }

        .info-block strong {
            display: inline-block;
            width: 80px;
        }

        .letter-body p {
            margin-bottom: 1.2em;
            text-align: justify;
        }

        .payment-details {
            margin: 30px 0;
            font-size: 13px;
        }

        .text_center {
            text-align: center !important;
        }

        .closing {
            margin-top: 40px;
        }

        .signature-block {
            margin-top: 80px;
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
    </style>
</head>
<body>
<div class="letter-container">
    <div class="letter-header">
        <img src="{{ $logoPath }}" alt="Logo">
        <h1>{{$title}}</h1>
        @if(!empty($accumulated_lot))
            <h1>{{$accumulated_lot}}</h1>
        @endif
    </div>

    <div class="info-block">
        <p><strong>Fecha:</strong> {{$date_emitted}}</p>
        <p><strong>Referencia:</strong> Cobranza activa</p>
        <p><strong>Propiedad:</strong> {{$associated['property']}}</p>
    </div>

    <div class="letter-body">
        <p>Estimado(a) {{$associated['name']}},</p>
        <p>
            Espero que este mensaje les encuentre bien. El propósito de esta carta es recordarle el pago correspondiente
            al mes de
            {{$period_month}}, el cual es necesario para el mantenimiento continuo y eficiente de nuestras instalaciones
            y servicios de planilla que todavía dependen de la Asociación.
        </p>
        <p>
            {!!$paragraph_amount!!}
        </p>

        <div class="payment-details">
            <p class="text_center"><strong>{{$bank_name}}: {{$bank_account}}.</strong></p>
            <p class="text_center"><strong>También pueden utilizar el CCI {{$bank_account_cci}}</strong></p>
        </div>

        <p>
            {!!$paragraph_thank_you!!}
        </p>

        <p class="closing">Atentamente,</p>
    </div>

    <div class="signature-block">
        <div class="line"></div>
        <p><strong>Rudy David Huaranga Bolaños, presidente</strong></p>
        <p>{{$title}}</p>
    </div>
</div>
</body>
</html>
