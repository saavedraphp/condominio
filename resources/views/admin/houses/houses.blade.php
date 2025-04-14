@extends('admin.layout.master')

@section('content')
    <div id="houses-container">
        <houses-list></houses-list>
    </div>
@endsection
@vite(['resources/js/admin/houses.js'])
