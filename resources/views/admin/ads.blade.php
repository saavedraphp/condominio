@extends('admin.layout.master')

@section('content')
    <div id="ads-container">
        <ads-list></ads-list>
    </div>
@endsection
@vite(['resources/js/admin/ads.js'])
