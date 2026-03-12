@extends('admin.layout.master')

@section('content')
    <div id="balance-sheet-container">
        <balance-sheet-list></balance-sheet-list>
    </div>
@endsection
@vite(['resources/js/admin/balance-sheet-report.js'])
