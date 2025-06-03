@extends('admin.layout.master')

@section('content')
    <div id="petty-cash-funds-container">
        <petty-cash-funds-list
            :url-base="'{{ route('admin.petty-cash.index') }}'"
        >
        </petty-cash-funds-list>
    </div>
@endsection
@vite(['resources/js/admin/petty-cash-funds.js'])
