@extends('layouts.admin')

@section('title', 'Payments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Payments</h1>
</div>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>ID</th><th>Booking</th><th>Method</th><th>Status</th><th>Amount</th><th>Paid at</th><th></th></tr></thead>
    <tbody>
    @foreach($payments as $p)
        <tr>
            <td>{{ $p->id }}</td>
            <td>
                #{{ $p->booking_id }}
                @if($p->booking)
                    <span class="text-muted small">— {{ $p->booking->court->name ?? '' }}</span>
                @endif
            </td>
            <td>{{ $p->payment_method->value }}</td>
            <td>{{ $p->status->value }}</td>
            <td>{{ $p->amount }}</td>
            <td>{{ $p->paid_at?->format('Y-m-d H:i') ?? '—' }}</td>
            <td class="text-end">
                <a href="{{ route('admin.payments.edit', $p) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('admin.payments.destroy', $p) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this payment record?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $payments->links() }}
@endsection
