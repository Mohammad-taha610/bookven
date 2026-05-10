@extends('layouts.admin')

@section('title', 'App users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">App users</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-bv btn-sm">Add user</a>
</div>
@if($errors->has('delete'))
    <div class="alert alert-danger">{{ $errors->first('delete') }}</div>
@endif
<p class="text-muted small">Members and branch managers who use the mobile app (not super admins).</p>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Branches</th><th></th></tr></thead>
    <tbody>
    @foreach($users as $u)
        <tr>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->role->value }}</td>
            <td>{{ $u->branches_count }}</td>
            <td class="text-end">
                <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('admin.users.destroy', $u) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this user? Bookings must be cleared first.');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $users->links() }}
@endsection
