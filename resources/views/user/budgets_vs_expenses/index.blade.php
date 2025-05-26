@extends('user.layout.master')
@section('content')
    <div id="budget-summary-container">
        <budget-summary-report/>
    </div>
@endsection
@vite(['resources/js/user/budgets-vs-expenses.js'])
