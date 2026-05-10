@extends('layouts.admin')

@section('title', 'Branch hours')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Branch hours</h1>
    <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary btn-sm">All branches</a>
</div>
<p class="text-muted small">Opening hours are stored per branch. Recurring bookable windows are managed under <a href="{{ route('admin.slots.index') }}">Slots</a>.</p>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Branch</th><th>Opening hours</th><th></th></tr></thead>
    <tbody>
    @foreach($branches as $b)
        <tr>
            <td>{{ $b->name }}</td>
            <td class="small">{{ $b->opening_hours ?: '—' }}</td>
            <td class="text-end">
                <a href="{{ route('admin.branches.edit', $b) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $branches->links() }}
@endsection
