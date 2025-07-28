@extends('admin.layout.master')

@section('content')
    <div id="other-expenses-container">
        <other-expenses-list
            :routes='@json($routes)'
        >
        </other-expenses-list>
    </div>
@endsection
@vite(['resources/js/admin/other-expenses.js'])
