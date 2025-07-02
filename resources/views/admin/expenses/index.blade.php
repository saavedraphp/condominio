@extends('admin.layout.master')

@section('content')
    <div id="expenses-container">
        <expenses-list
            :url-base='@json($routes)'
            :budget-scope="'{{$budget_scope}}'"
            :meta='@json($meta)'
        >
        </expenses-list>
    </div>
@endsection
@vite(['resources/js/admin/expenses.js'])
