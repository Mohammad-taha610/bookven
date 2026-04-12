@extends('layouts.admin')

@section('title', 'Edit user')

@section('content')
<h1 class="h3 mb-3">Edit user</h1>
<form method="post" action="{{ route('admin.users.update', $user) }}" class="bg-white shadow-sm p-4 rounded">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
            <option value="user" @selected(old('role', $user->role->value) === 'user')>User</option>
            <option value="manager" @selected(old('role', $user->role->value) === 'manager')>Branch manager</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Allowed branches</label>
        @php
            $selected = collect(old('branch_ids', $user->branches->pluck('id')->all()));
        @endphp
        @foreach($branches as $b)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="branch_ids[]" value="{{ $b->id }}" id="b{{ $b->id }}" @checked($selected->contains($b->id))>
                <label class="form-check-label" for="b{{ $b->id }}">{{ $b->name }}</label>
            </div>
        @endforeach
    </div>
    <div class="mb-3">
        <label class="form-label">New password (optional)</label>
        <input name="password" type="password" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Confirm password</label>
        <input name="password_confirmation" type="password" class="form-control">
    </div>
    <button class="btn btn-bv" type="submit">Update</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-link">Back</a>
</form>
@endsection
