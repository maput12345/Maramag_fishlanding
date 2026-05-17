@extends('layouts.admin')

@php
    $breadcrumbs = [
        ['title' => 'Fish Box Tracking']
    ];
@endphp

@section('content')
<div class="w-full">
    @include('admin.sales.fishbox-tracking')
</div>
@endsection
