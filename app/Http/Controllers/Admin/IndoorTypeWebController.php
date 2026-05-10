<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndoorType;
use Illuminate\Http\Request;

class IndoorTypeWebController extends Controller
{
    public function index()
    {
        $indoorTypes = IndoorType::query()
            ->withCount('courts')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.indoor-types.index', compact('indoorTypes'));
    }

    public function create()
    {
        return view('admin.indoor-types.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_-]+$/', 'unique:indoor_types,slug'],
            'name' => ['required', 'string', 'max:255'],
            'icon_key' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_-]+$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        IndoorType::create($data);

        return redirect()->route('admin.indoor-types.index')->with('status', 'Indoor type created.');
    }

    public function edit(IndoorType $indoor_type)
    {
        return view('admin.indoor-types.edit', ['indoorType' => $indoor_type]);
    }

    public function update(Request $request, IndoorType $indoor_type)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon_key' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_-]+$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $indoor_type->update($data);

        return redirect()->route('admin.indoor-types.index')->with('status', 'Indoor type updated.');
    }

    public function destroy(IndoorType $indoor_type)
    {
        if ($indoor_type->courts()->exists()) {
            return redirect()
                ->route('admin.indoor-types.index')
                ->withErrors(['delete' => 'Reassign courts that use this indoor type before deleting it.']);
        }

        if (IndoorType::query()->count() <= 1) {
            return redirect()
                ->route('admin.indoor-types.index')
                ->withErrors(['delete' => 'At least one indoor type must remain.']);
        }

        $indoor_type->delete();

        return redirect()->route('admin.indoor-types.index')->with('status', 'Indoor type deleted.');
    }
}
