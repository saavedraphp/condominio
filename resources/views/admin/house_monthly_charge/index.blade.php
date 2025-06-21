@extends('admin.layout.master')

@section('content')
    <div id="house-charge-container">
        <house-monthly-charge-list
            :url-base="'{{ route('admin.house-monthly-charges.index') }}'"
        >
        </house-monthly-charge-list>
    </div>
@endsection
@vite(['resources/js/admin/house-monthly-charge.js'])
