@extends('layouts.admin')

@section('title', 'Bookings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Bookings</h1>
</div>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>ID</th><th>Date</th><th>Status</th><th>User</th><th>Court</th><th>Slot</th><th>Amount</th><th></th></tr></thead>
    <tbody>
    @foreach($bookings as $b)
        <tr>
            <td>{{ $b->id }}</td>
            <td>{{ $b->date->format('Y-m-d') }}</td>
            <td>{{ $b->status->value }}</td>
            <td>{{ $b->user->name ?? '—' }}</td>
            <td>{{ $b->court->name ?? '—' }}</td>
            <td>
                @if($b->slot)
                    {{ \Illuminate\Support\Str::substr($b->slot->start_time, 0, 5) }}–{{ \Illuminate\Support\Str::substr($b->slot->end_time, 0, 5) }}
                @else
                    —
                @endif
            </td>
            <td>{{ $b->amount }}</td>
            <td class="text-end">
                <a href="{{ route('admin.bookings.edit', $b) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('admin.bookings.destroy', $b) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this booking?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $bookings->links() }}
@endsection
