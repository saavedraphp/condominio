{{-- Página 1: La carta de cobranza --}}
@include('pdf.collection_latter')

{{-- Esto fuerza a dompdf a crear una nueva página --}}
<!--<div style="page-break-after: always;"></div>-->

{{-- Página 2: El recibo que ya tenías --}}
@include('pdf.monthly_maintenance_receipt')
