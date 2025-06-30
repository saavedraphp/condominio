@extends('admin.layout.master')

@section('content')
    <div id="report-balance-due-container">
        <report-balance-due-list></report-balance-due-list>
    </div>
@endsection
@vite(['resources/js/admin/report-balance-due.js'])
