@extends('admin.layout.master')

@section('content')
    <div id="income-statement-container">
        <income-statement-list></income-statement-list>
    </div>
@endsection
@vite(['resources/js/admin/income-statement-report.js'])
