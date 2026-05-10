@extends('layouts.admin')

@section('title', 'New indoor type')

@section('content')
<h1 class="h3 mb-3">New indoor type</h1>
<form method="post" action="{{ route('admin.indoor-types.store') }}" class="bg-white shadow-sm p-4 rounded">
    @csrf
    <div class="mb-3">
        <label class="form-label">Slug</label>
        <input name="slug" class="form-control" value="{{ old('slug') }}" required pattern="[a-z0-9_-]+" title="Lowercase letters, numbers, hyphen, underscore">
        <div class="form-text">Stable API identifier (e.g. <code>court</code>, <code>net</code>, <code>padel</code>).</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Display name</label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Icon key</label>
        <input name="icon_key" class="form-control" value="{{ old('icon_key') }}" required pattern="[a-z0-9_-]+">
    </div>
    <div class="mb-3">
        <label class="form-label">Sort order</label>
        <input name="sort_order" type="number" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
    </div>
    <button class="btn btn-bv" type="submit">Save</button>
    <a href="{{ route('admin.indoor-types.index') }}" class="btn btn-link">Cancel</a>
</form>
@endsection
