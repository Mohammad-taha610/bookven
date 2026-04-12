@extends('layouts.admin')

@section('title', 'Branches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Branches</h1>
    <a href="{{ route('admin.branches.create') }}" class="btn btn-bv btn-sm">Add branch</a>
</div>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Name</th><th>Latitude</th><th>Longitude</th><th>Phone</th><th></th></tr></thead>
    <tbody>
    @foreach($branches as $b)
        <tr>
            <td>{{ $b->name }}</td>
            <td>{{ $b->latitude !== null ? number_format($b->latitude, 6) : '—' }}</td>
            <td>{{ $b->longitude !== null ? number_format($b->longitude, 6) : '—' }}</td>
            <td>{{ $b->phone }}</td>
            <td class="text-end">
                <a href="{{ route('admin.branches.edit', $b) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('admin.branches.destroy', $b) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this branch?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $branches->links() }}
@endsection
