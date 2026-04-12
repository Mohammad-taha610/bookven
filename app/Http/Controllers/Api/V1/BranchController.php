<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $branches = $request->user()
            ->accessibleBranchesQuery()
            ->orderBy('name')
            ->get();

        return $this->jsonSuccess(BranchResource::collection($branches));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Branch::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:32'],
            'opening_hours' => ['nullable', 'string'],
        ]);

        $branch = Branch::create($data);

        return $this->jsonSuccess(new BranchResource($branch), 'Branch created.', 201);
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorize('update', $branch);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:32'],
            'opening_hours' => ['nullable', 'string'],
        ]);

        $branch->update($data);

        return $this->jsonSuccess(new BranchResource($branch->fresh()), 'Branch updated.');
    }

    public function destroy(Branch $branch)
    {
        $this->authorize('delete', $branch);
        $branch->delete();

        return $this->jsonSuccess(null, 'Branch deleted.');
    }
}
