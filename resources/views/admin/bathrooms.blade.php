@extends('admin.layout.master')

@section('content')
    <div id="bathrooms-container">
        <div>Baños</div>
        <bathrooms></bathrooms>
    </div>
@endsection
@vite(['resources/js/admin/bathrooms.js'])
