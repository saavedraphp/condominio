@extends('admin.layout.master')

@section('content')
    <div id="qr-codes-container">
        <qr-codes-list
            :routes='@json($routes)'
        >
        </qr-codes-list>
    </div>
@endsection
@vite(['resources/js/admin/qr-codes.js'])
