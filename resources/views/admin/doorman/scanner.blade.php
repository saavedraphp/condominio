@extends('admin.layout.master')

@section('content')
    <div id="doorman-scanner-container">
        <doorman-scanner></doorman-scanner>
    </div>
@endsection
@vite(['resources/js/admin/doorman-scanner.js'])
