@extends('layouts.admin')

@section('title', 'Indoor types')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Indoor types</h1>
    <a href="{{ route('admin.indoor-types.create') }}" class="btn btn-bv btn-sm">Add indoor type</a>
</div>
<p class="text-muted small">Categories for the app “Indoor type” filter (slug is sent to the API as <code>indoor_facility_kind</code>).</p>
@if($errors->has('delete'))
    <div class="alert alert-danger">{{ $errors->first('delete') }}</div>
@endif
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Sort</th><th>Name</th><th>Slug</th><th>Icon key</th><th>Courts</th><th></th></tr></thead>
    <tbody>
    @foreach($indoorTypes as $t)
        <tr>
            <td>{{ $t->sort_order }}</td>
            <td>{{ $t->name }}</td>
            <td><code class="small">{{ $t->slug }}</code></td>
            <td><code class="small">{{ $t->icon_key }}</code></td>
            <td>{{ $t->courts_count }}</td>
            <td class="text-end">
                <a href="{{ route('admin.indoor-types.edit', $t) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('admin.indoor-types.destroy', $t) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this indoor type?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $indoorTypes->links() }}
@endsection
