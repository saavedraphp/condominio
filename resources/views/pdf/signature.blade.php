<style>
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
</style>
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
