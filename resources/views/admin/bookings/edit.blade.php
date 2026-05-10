@extends('layouts.admin')

@section('title', 'Edit booking')

@section('content')
<h1 class="h3 mb-3">Edit booking #{{ $booking->id }}</h1>
<p class="text-muted small">Court, slot, and member are fixed here; adjust status, date, customer details, and amounts.</p>
<div class="bg-white shadow-sm p-4 rounded mb-3">
    <div class="row small">
        <div class="col-md-6 mb-2"><strong>Court:</strong> {{ $booking->court->name ?? '—' }} ({{ $booking->court->branch->name ?? '—' }})</div>
        <div class="col-md-6 mb-2"><strong>Slot:</strong>
            @if($booking->slot)
                {{ \Illuminate\Support\Str::substr($booking->slot->start_time, 0, 5) }}–{{ \Illuminate\Support\Str::substr($booking->slot->end_time, 0, 5) }}, day {{ $booking->slot->day_of_week }}
            @else
                —
            @endif
        </div>
        <div class="col-md-6 mb-2"><strong>User:</strong> {{ $booking->user->name ?? '—' }} ({{ $booking->user->email ?? '' }})</div>
    </div>
</div>
<form method="post" action="{{ route('admin.bookings.update', $booking) }}" class="bg-white shadow-sm p-4 rounded">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            @foreach($statuses as $st)
                <option value="{{ $st->value }}" @selected(old('status', $booking->status->value) === $st->value)>{{ $st->value }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Date</label>
        <input name="date" type="date" class="form-control" value="{{ old('date', $booking->date->format('Y-m-d')) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Customer name</label>
        <input name="customer_name" class="form-control" value="{{ old('customer_name', $booking->customer_name) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Customer phone</label>
        <input name="customer_phone" class="form-control" value="{{ old('customer_phone', $booking->customer_phone) }}">
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Amount</label>
            <input name="amount" type="number" step="0.01" class="form-control" value="{{ old('amount', $booking->amount) }}" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Advance</label>
            <input name="advance_amount" type="number" step="0.01" class="form-control" value="{{ old('advance_amount', $booking->advance_amount) }}" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Remaining</label>
            <input name="remaining_amount" type="number" step="0.01" class="form-control" value="{{ old('remaining_amount', $booking->remaining_amount) }}" required>
        </div>
    </div>
    <button class="btn btn-bv" type="submit">Update</button>
    <a href="{{ route('admin.bookings.index') }}" class="btn btn-link">Back</a>
</form>
@endsection
