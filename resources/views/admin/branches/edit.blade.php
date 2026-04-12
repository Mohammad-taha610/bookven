@extends('layouts.admin')

@section('title', 'Edit branch')

@section('content')
<h1 class="h3 mb-3">Edit branch</h1>
<form method="post" action="{{ route('admin.branches.update', $branch) }}" class="bg-white shadow-sm p-4 rounded">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="{{ old('name', $branch->name) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Address</label>
        <input name="address" class="form-control" value="{{ old('address', $branch->address) }}">
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Latitude</label>
            <input name="latitude" type="text" inputmode="decimal" class="form-control" value="{{ old('latitude', $branch->latitude) }}" placeholder="e.g. 51.5074">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Longitude</label>
            <input name="longitude" type="text" inputmode="decimal" class="form-control" value="{{ old('longitude', $branch->longitude) }}" placeholder="e.g. -0.1278">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control" value="{{ old('phone', $branch->phone) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Opening hours</label>
        <textarea name="opening_hours" class="form-control" rows="2">{{ old('opening_hours', $branch->opening_hours) }}</textarea>
    </div>
    <button class="btn btn-bv" type="submit">Update</button>
    <a href="{{ route('admin.branches.index') }}" class="btn btn-link">Back</a>
</form>
@endsection
