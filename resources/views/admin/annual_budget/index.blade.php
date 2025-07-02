@extends('admin.layout.master')

@section('content')
    <div id="annual-budget-container">
        <annual-budget-list
            :url-base='@json($routes)'
            :budget-scope="'{{$budget_scope}}'"
            :meta='@json($meta)'
        >
        </annual-budget-list>
    </div>
@endsection
@vite(['resources/js/admin/annual-budget.js'])
