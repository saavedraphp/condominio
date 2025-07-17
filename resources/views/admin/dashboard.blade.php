@extends('admin.layout.master')

@section('content')
    <div id="dashboard-container">
        @if($dashboard_visible)
            <dashboard>
            </dashboard>
        @endif
    </div>

@endsection
@vite(['resources/js/admin/dashboard.js'])
