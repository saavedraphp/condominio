@extends('admin.layout.master')

@section('content')
    <div id="securities-container">
        <security-list
            :routes='@json($routes)'
        >
        </security-list>
    </div>
@endsection
@vite(['resources/js/admin/securities.js'])
