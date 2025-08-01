@extends('admin.layout.master')

@section('content')
    <div id="expenses-report-container">
        <expense-report-list></expense-report-list>
    </div>
@endsection
@vite(['resources/js/admin/expenses-report.js'])
