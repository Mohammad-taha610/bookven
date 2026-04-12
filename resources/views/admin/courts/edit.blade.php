@extends('layouts.admin')

@section('title', 'Edit court')

@section('content')
<h1 class="h3 mb-3">Edit court</h1>
<form method="post" action="{{ route('admin.courts.update', $court) }}" class="bg-white shadow-sm p-4 rounded">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select" required>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(old('branch_id', $court->branch_id) == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="{{ old('name', $court->name) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" required>
            @foreach($types as $t)
                <option value="{{ $t->value }}" @selected(old('type', $court->type->value) === $t->value)>{{ $t->value }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Capacity</label>
        <input name="capacity" type="number" class="form-control" value="{{ old('capacity', $court->capacity) }}" min="1">
    </div>
    <div class="mb-3">
        <label class="form-label">Price per hour</label>
        <input name="price_per_hour" type="number" step="0.01" class="form-control" value="{{ old('price_per_hour', $court->price_per_hour) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Image URL</label>
        <input name="image_url" class="form-control" value="{{ old('image_url', $court->image_url) }}">
    </div>
    <button class="btn btn-bv" type="submit">Update</button>
    <a href="{{ route('admin.courts.index') }}" class="btn btn-link">Back</a>
</form>
@endsection
