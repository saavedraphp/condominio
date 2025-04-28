@extends('admin.layout.master')

@section('content')
    <div id="payment-service-container">
        <electricity-consumption-list
            :user-id="'{{ $webUserId }}'"
            :house-id="'{{ $houseId }}'"
            type-service-id="{{ $typeServiceId }}"
        >
        </electricity-consumption-list>
    </div>
@endsection
@vite(['resources/js/user/electricity-consumption.js'])
