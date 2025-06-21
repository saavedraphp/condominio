@extends('admin.layout.master')

@section('content')
    <div id="petty-cash-funds-transactions-container">
        <petty-cash-transactions-list
            :fund-id="{{ $fund_id }}"
            url-base="{{ route('admin.petty-cash.funds.transactions.index', $fund_id) }}"
        >
        </petty-cash-transactions-list>
    </div>
@endsection
@vite(['resources/js/admin/petty-cash-funds-transactions.js'])
