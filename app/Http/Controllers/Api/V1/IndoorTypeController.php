<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IndoorType;

class IndoorTypeController extends Controller
{
    /**
     * Indoor facility categories shown as "Indoor Type" in the mobile app (e.g. Court vs Net).
     */
    public function index()
    {
        $items = IndoorType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (IndoorType $t) => [
                'id' => $t->slug,
                'key' => $t->slug,
                'label' => $t->name,
                'icon_key' => $t->icon_key,
            ]);

        return $this->jsonSuccess($items->values());
    }
}
