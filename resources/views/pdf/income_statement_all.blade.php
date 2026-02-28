@include('pdf.income_statement')

{{-- Esto fuerza a dompdf a crear una nueva página --}}
<!--<div style="page-break-after: always;"></div>-->

{{-- Página 2: El recibo que ya tenías --}}
@include('pdf.income_statement_images_expenses')
