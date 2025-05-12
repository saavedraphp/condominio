@extends('admin.layout.master')
@section('content')
    <div id="house-attribute-container">
        <house-attribute
            :user='@json($webUser)'
            :house='@json($house)'
        >
        </house-attribute>
    </div>
@endsection
@vite(['resources/js/admin/house-attribute.js'])
