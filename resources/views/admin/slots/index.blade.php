@extends('layouts.admin')

@section('title', 'Slots')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Slots</h1>
    <a href="{{ route('admin.slots.create') }}" class="btn btn-bv btn-sm">Add slots</a>
</div>
<p class="text-muted small">Weekly recurring time windows per court (day of week + start/end). Bookings pick one slot on a specific date.</p>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Court</th><th>Branch</th><th>Day</th><th>Start</th><th>End</th><th></th></tr></thead>
    <tbody>
    @foreach($slots as $s)
        <tr>
            <td>{{ $s->court->name ?? '—' }}</td>
            <td>{{ $s->court->branch->name ?? '—' }}</td>
            <td>{{ $dayNames[$s->day_of_week] ?? $s->day_of_week }}</td>
            <td>{{ \Illuminate\Support\Str::substr($s->start_time, 0, 5) }}</td>
            <td>{{ \Illuminate\Support\Str::substr($s->end_time, 0, 5) }}</td>
            <td class="text-end">
                <a href="{{ route('admin.slots.edit', $s) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('admin.slots.destroy', $s) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this slot template?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $slots->links() }}
@endsection
