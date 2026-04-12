<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserWebController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->whereIn('role', [UserRole::User->value, UserRole::Manager->value])
            ->withCount('branches')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $branches = Branch::query()->orderBy('name')->get();

        return view('admin.users.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::in([UserRole::User->value, UserRole::Manager->value])],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'role' => UserRole::from($data['role']),
        ]);

        $user->branches()->sync($data['branch_ids']);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user)
    {
        if (! in_array($user->role, [UserRole::User, UserRole::Manager], true)) {
            abort(404);
        }

        $branches = Branch::query()->orderBy('name')->get();
        $user->load('branches');

        return view('admin.users.edit', compact('user', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        if (! in_array($user->role, [UserRole::User, UserRole::Manager], true)) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::in([UserRole::User->value, UserRole::Manager->value])],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->role = UserRole::from($data['role']);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $user->branches()->sync($data['branch_ids']);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }
}
