@extends('layouts.admin')

@section('title', 'New branch')

@section('content')
<h1 class="h3 mb-3">New branch</h1>
<form method="post" action="{{ route('admin.branches.store') }}" class="bg-white shadow-sm p-4 rounded">
    @csrf
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Address</label>
        <input name="address" class="form-control" value="{{ old('address') }}">
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Latitude</label>
            <input name="latitude" type="text" inputmode="decimal" class="form-control" value="{{ old('latitude') }}" placeholder="e.g. 51.5074">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Longitude</label>
            <input name="longitude" type="text" inputmode="decimal" class="form-control" value="{{ old('longitude') }}" placeholder="e.g. -0.1278">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control" value="{{ old('phone') }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Opening hours</label>
        <textarea name="opening_hours" class="form-control" rows="2">{{ old('opening_hours') }}</textarea>
    </div>
    <button class="btn btn-bv" type="submit">Save</button>
    <a href="{{ route('admin.branches.index') }}" class="btn btn-link">Cancel</a>
</form>
@endsection
