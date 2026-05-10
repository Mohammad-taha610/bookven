@extends('layouts.admin')

@section('title', 'Edit indoor type')

@section('content')
<h1 class="h3 mb-3">Edit indoor type</h1>
<form method="post" action="{{ route('admin.indoor-types.update', $indoorType) }}" class="bg-white shadow-sm p-4 rounded">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Slug</label>
        <input class="form-control" value="{{ $indoorType->slug }}" disabled>
        <div class="form-text">Slug cannot be changed (courts reference it). Create a new type and reassign courts if needed.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Display name</label>
        <input name="name" class="form-control" value="{{ old('name', $indoorType->name) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Icon key</label>
        <input name="icon_key" class="form-control" value="{{ old('icon_key', $indoorType->icon_key) }}" required pattern="[a-z0-9_-]+">
    </div>
    <div class="mb-3">
        <label class="form-label">Sort order</label>
        <input name="sort_order" type="number" class="form-control" value="{{ old('sort_order', $indoorType->sort_order) }}" min="0">
    </div>
    <button class="btn btn-bv" type="submit">Update</button>
    <a href="{{ route('admin.indoor-types.index') }}" class="btn btn-link">Back</a>
</form>
@endsection
