<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CourtType;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourtResource;
use App\Models\Branch;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourtController extends Controller
{
    public function indexForBranch(Request $request, Branch $branch)
    {
        if (! $request->user()->canAccessBranch($branch)) {
            return $this->jsonError('You do not have access to this branch.', 403);
        }

        $courts = $branch->courts()->orderBy('name')->get();

        return $this->jsonSuccess(CourtResource::collection($courts));
    }

    public function show(Request $request, Court $court)
    {
        if (! $request->user()->canAccessCourt($court)) {
            return $this->jsonError('You do not have access to this court.', 403);
        }

        $court->load('branch');

        return $this->jsonSuccess(new CourtResource($court));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Court::class);

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CourtType::class)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'price_per_hour' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);

        if (! $request->user()->canAccessBranchId((int) $data['branch_id'])) {
            return $this->jsonError('You do not have access to this branch.', 403);
        }

        $court = Court::create($data);
        $court->load('branch');

        return $this->jsonSuccess(new CourtResource($court), 'Court created.', 201);
    }

    public function update(Request $request, Court $court)
    {
        $this->authorize('update', $court);

        $data = $request->validate([
            'branch_id' => ['sometimes', 'exists:branches,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(CourtType::class)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'price_per_hour' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);

        if (isset($data['branch_id']) && ! $request->user()->canAccessBranchId((int) $data['branch_id'])) {
            return $this->jsonError('You do not have access to this branch.', 403);
        }

        $court->update($data);
        $court->load('branch');

        return $this->jsonSuccess(new CourtResource($court->fresh()), 'Court updated.');
    }

    public function destroy(Court $court)
    {
        $this->authorize('delete', $court);
        $court->delete();

        return $this->jsonSuccess(null, 'Court deleted.');
    }
}
