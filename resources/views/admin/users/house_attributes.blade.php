@extends('admin.layout.master')
@section('content')
    <div id="house-attribute-container" style="padding: 20px">
        <house-attribute
            :user='@json($webUser)'
            :house='@json($house)'
            :is-admin='@json($is_admin)'
        >
        </house-attribute>
    </div>
@endsection
@vite(['resources/js/admin/house-attribute.js'])
