<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;

class BranchTimingsWebController extends Controller
{
    /**
     * Branch-level opening hours (human-readable); weekly slot templates are under Slots.
     */
    public function index()
    {
        $branches = Branch::query()->orderBy('name')->paginate(15);

        return view('admin.timings.index', compact('branches'));
    }
}
