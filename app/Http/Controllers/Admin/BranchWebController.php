<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchWebController extends Controller
{
    public function index()
    {
        $branches = Branch::query()->orderBy('name')->paginate(15);

        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:32'],
            'opening_hours' => ['nullable', 'string'],
        ]);

        Branch::create($data);

        return redirect()->route('admin.branches.index')->with('status', 'Branch created.');
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:32'],
            'opening_hours' => ['nullable', 'string'],
        ]);

        $branch->update($data);

        return redirect()->route('admin.branches.index')->with('status', 'Branch updated.');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return redirect()->route('admin.branches.index')->with('status', 'Branch deleted.');
    }
}
