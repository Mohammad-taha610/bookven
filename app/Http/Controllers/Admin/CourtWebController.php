<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourtType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourtWebController extends Controller
{
    public function index()
    {
        $courts = Court::query()->with('branch')->orderBy('name')->paginate(15);

        return view('admin.courts.index', compact('courts'));
    }

    public function create()
    {
        $branches = Branch::query()->orderBy('name')->get();
        $types = CourtType::cases();

        return view('admin.courts.create', compact('branches', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CourtType::class)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'price_per_hour' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);

        Court::create($data);

        return redirect()->route('admin.courts.index')->with('status', 'Court created.');
    }

    public function edit(Court $court)
    {
        $branches = Branch::query()->orderBy('name')->get();
        $types = CourtType::cases();

        return view('admin.courts.edit', compact('court', 'branches', 'types'));
    }

    public function update(Request $request, Court $court)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CourtType::class)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'price_per_hour' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $court->update($data);

        return redirect()->route('admin.courts.index')->with('status', 'Court updated.');
    }

    public function destroy(Court $court)
    {
        $court->delete();

        return redirect()->route('admin.courts.index')->with('status', 'Court deleted.');
    }
}
