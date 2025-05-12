@extends('admin.layout.master')

@section('content')
    <div id="payment-service-container">
        <electricity-consumption-list
            :user-id="'{{ $webUserId ?? null }}'"
            :house-id="'{{ $houseId ?? null }}'"
            :is-admin="{{ $isAdmin ? 'true' : 'false' }}"
            :type-service-id={{ $typeServiceId }}
        >
        </electricity-consumption-list>
    </div>
@endsection
@vite(['resources/js/user/electricity-consumption.js'])
