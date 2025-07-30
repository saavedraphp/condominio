@extends('admin.layout.master')

@section('content')
    <div id="payment-report-container">
        <payment-report-list></payment-report-list>
    </div>
@endsection
@vite(['resources/js/admin/payment-report.js'])
