@extends('admin.layout.master')

@section('content')
    <div id="expenses-container">
        <expenses-list
            :url-base="'{{ route('admin.expenses.index') }}'"
            :url-annual-budget="'{{ route('admin.annual-budget.index') }}'"
        >
        </expenses-list>
    </div>
@endsection
@vite(['resources/js/admin/expenses.js'])
