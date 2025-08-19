@extends('admin.layout.master')

@section('content')
    <div id="qr-scan-container">
<scan-security></scan-security>
    </div>
@endsection
@vite(['resources/js/securities/qr-scan.js'])
