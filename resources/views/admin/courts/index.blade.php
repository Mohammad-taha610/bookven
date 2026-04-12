@extends('layouts.admin')

@section('title', 'Courts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Courts</h1>
    <a href="{{ route('admin.courts.create') }}" class="btn btn-bv btn-sm">Add court</a>
</div>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Name</th><th>Branch</th><th>Type</th><th>Price/hr</th><th></th></tr></thead>
    <tbody>
    @foreach($courts as $c)
        <tr>
            <td>{{ $c->name }}</td>
            <td>{{ $c->branch->name ?? '—' }}</td>
            <td>{{ $c->type->value }}</td>
            <td>{{ $c->price_per_hour }}</td>
            <td class="text-end">
                <a href="{{ route('admin.courts.edit', $c) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('admin.courts.destroy', $c) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this court?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $courts->links() }}
@endsection
