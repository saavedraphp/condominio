@extends('user.layout.master')
@section('content')
    <div id="qr-user-container">
        <user-qr-code/>
    </div>
@endsection
@vite(['resources/js/user/qr-code.js'])
