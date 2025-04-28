@extends('admin.layout.master')

@section('content')
    <div id="dashboard-by-house-container">
        <dashboard-by-house
            :user-id="'{{ $webUserId }}'"
            :house-id="'{{ $houseId }}'"
        >
        </dashboard-by-house>
    </div>
@endsection
@vite(['resources/js/user/dashboard-by-house.js'])
