@extends('admin.layout.master')

@section('content')
    <div id="doorman-scanner-container">
        <visit-pass-scanner></visit-pass-scanner>
    </div>
@endsection
@vite(['resources/js/admin/visit-pass-scanner.js'])
