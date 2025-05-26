@extends('admin.layout.master')

@section('content')
    <div id="annual-budget-container">
        <annual-budget-list
            :url-base="'{{ route('admin.annual-budget.index') }}'"
        >
        </annual-budget-list>
    </div>
@endsection
@vite(['resources/js/admin/annual-budget.js'])
