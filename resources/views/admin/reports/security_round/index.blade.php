@extends('admin.layout.master')

@section('content')
    <div id="security-round-report-container">
        <security-round-report-list
            :routes='@json($routes)'
        >
        </security-round-report-list>
    </div>
@endsection
@vite(['resources/js/admin/security-round-report.js'])
