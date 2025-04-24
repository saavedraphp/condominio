@extends('user.layout.master')
@section('content')
    <div id="payment-list-container">
        <payment-list
            :user='@json($webUser)'
            :house='@json($house)'
        ></payment-list>
    </div>
@endsection
@vite(['resources/js/user/payment.js'])
