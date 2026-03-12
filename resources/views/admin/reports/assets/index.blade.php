@extends('admin.layout.master')

@section('content')
    <div id="asset-container">
        <asset-list></asset-list>
    </div>
@endsection
@vite(['resources/js/admin/asset-report.js'])
