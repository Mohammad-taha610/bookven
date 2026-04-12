@extends('layouts.admin')

@section('title', 'App users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">App users</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-bv btn-sm">Add user</a>
</div>
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
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $users->links() }}
@endsection
