@extends('layouts.admin')

@section('title', 'New app user')

@section('content')
<h1 class="h3 mb-3">New app user</h1>
<form method="post" action="{{ route('admin.users.store') }}" class="bg-white shadow-sm p-4 rounded">
    @csrf
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" value="{{ old('email') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control" value="{{ old('phone') }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
            <option value="user" @selected(old('role') === 'user')>User</option>
            <option value="manager" @selected(old('role') === 'manager')>Branch manager</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Allowed branches</label>
        <p class="text-muted small">Users only see courts and bookings for venues they are assigned to.</p>
        @foreach($branches as $b)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="branch_ids[]" value="{{ $b->id }}" id="b{{ $b->id }}" @checked(collect(old('branch_ids', []))->contains($b->id))>
                <label class="form-check-label" for="b{{ $b->id }}">{{ $b->name }}</label>
            </div>
        @endforeach
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Confirm password</label>
        <input name="password_confirmation" type="password" class="form-control" required>
    </div>
    <button class="btn btn-bv" type="submit">Create user</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-link">Cancel</a>
</form>
@endsection
