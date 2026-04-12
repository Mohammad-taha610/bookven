@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<h1 class="h3 mb-4">Dashboard</h1>
<div class="row g-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Branches</div>
                <div class="fs-3 fw-semibold">{{ $branches_count }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Courts</div>
                <div class="fs-3 fw-semibold">{{ $courts_count }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">App users</div>
                <div class="fs-3 fw-semibold">{{ $users_count }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Pending bookings</div>
                <div class="fs-3 fw-semibold">{{ $bookings_pending }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
