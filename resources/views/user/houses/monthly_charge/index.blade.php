@extends('admin.layout.master')

@section('content')
    <div id="house-charge-container">
        <house-monthly-charge-list
            :url-base='@json($routes)'
            :house-id="'{{ $house_id ?? null }}'"
            :meta='@json($meta)'
            :is-admin='@json($is_admin)'
        >
        </house-monthly-charge-list>
    </div>
@endsection
@vite(['resources/js/admin/house-monthly-charge.js'])
