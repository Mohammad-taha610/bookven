@extends('layouts.admin')

@section('title', 'Edit payment')

@section('content')
<h1 class="h3 mb-3">Edit payment #{{ $payment->id }}</h1>
@if($payment->booking)
    <p class="text-muted small mb-3">
        Booking #{{ $payment->booking_id }} — {{ $payment->booking->court->name ?? '' }}
        @if($payment->booking->user)
            — {{ $payment->booking->user->name }}
        @endif
    </p>
@endif
<form method="post" action="{{ route('admin.payments.update', $payment) }}" class="bg-white shadow-sm p-4 rounded">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Method</label>
        <select name="payment_method" class="form-select" required>
            @foreach($methods as $m)
                <option value="{{ $m->value }}" @selected(old('payment_method', $payment->payment_method->value) === $m->value)>{{ $m->value }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            @foreach($statuses as $s)
                <option value="{{ $s->value }}" @selected(old('status', $payment->status->value) === $s->value)>{{ $s->value }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Amount</label>
        <input name="amount" type="number" step="0.01" class="form-control" value="{{ old('amount', $payment->amount) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Paid at</label>
        <input name="paid_at" type="datetime-local" class="form-control" value="{{ old('paid_at', $payment->paid_at?->format('Y-m-d\TH:i')) }}">
        <div class="form-text">Leave empty when marking Completed to default to “now”. Cleared when status is not Completed.</div>
    </div>
    <button class="btn btn-bv" type="submit">Update</button>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-link">Back</a>
</form>
@endsection
