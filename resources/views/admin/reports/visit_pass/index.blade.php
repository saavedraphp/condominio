@extends('admin.layout.master')

@section('content')
    <div id="visit-pass-report-container">
        <visit-pass-report-list
            :routes='@json($routes)'
        >
        </visit-pass-report-list>
    </div>
@endsection
@vite(['resources/js/admin/visit-pass-report.js'])
