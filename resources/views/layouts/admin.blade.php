<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Bookven Admin')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --bv-green: #0d5c2e; --bv-green-light: #e8f5ec; }
        .navbar-brand { font-weight: 700; letter-spacing: .02em; }
        .btn-bv { background: var(--bv-green); border-color: var(--bv-green); color: #fff; }
        .btn-bv:hover { filter: brightness(1.08); color: #fff; }
        .sidebar { min-height: calc(100vh - 56px); background: #f8faf8; border-right: 1px solid #e0e8e2; }
        .sidebar a { color: #1a3324; text-decoration: none; }
        .sidebar a:hover { color: var(--bv-green); }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark" style="background: var(--bv-green);">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Bookven</a>
        @auth
            <form action="{{ route('admin.logout') }}" method="post" class="d-flex align-items-center gap-3">
                @csrf
                <span class="text-white-50 small">{{ auth()->user()->email }}</span>
                <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
            </form>
        @endauth
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
        @auth
            <aside class="col-md-2 py-4 sidebar">
                <ul class="nav flex-column gap-2 small">
                    <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li><a href="{{ route('admin.branches.index') }}">Branches</a></li>
                    <li><a href="{{ route('admin.courts.index') }}">Courts</a></li>
                    <li><a href="{{ route('admin.users.index') }}">App users</a></li>
                </ul>
            </aside>
        @endauth
        <main class="{{ auth()->check() ? 'col-md-10' : 'col-12' }} py-4">
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
