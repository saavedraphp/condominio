@extends('admin.layout.master')

@section('content')
    <div id="balance-by-associate-report-container">
        <balance-associate-report-list
            :routes='@json($routes)'
        >
        </balance-associate-report-list>
    </div>
@endsection
@vite(['resources/js/admin/balance-associate-report.js'])
