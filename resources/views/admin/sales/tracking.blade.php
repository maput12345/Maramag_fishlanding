@extends('layouts.admin')

@php
    $breadcrumbs = [
        ['title' => 'Fish Box Tracking']
    ];
@endphp

@section('content')
<div class="w-full">
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm mb-8">
        <div class="app-page-header">
            <div class="app-page-header__content">
                <h1 class="app-page-title">Fish Box Tracking</h1>
            </div>
        </div>
    </section>

    @include('admin.sales.fishbox-tracking')
</div>
@endsection
