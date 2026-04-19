<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IndoorFacilityKind;
use App\Http\Controllers\Controller;

class IndoorTypeController extends Controller
{
    /**
     * Indoor facility categories shown as "Indoor Type" in the mobile app (e.g. Court vs Net).
     */
    public function index()
    {
        $items = collect(IndoorFacilityKind::cases())->map(fn (IndoorFacilityKind $k) => [
            'id' => $k->value,
            'key' => $k->value,
            'label' => $k->label(),
            'icon_key' => $k->iconKey(),
        ]);

        return $this->jsonSuccess($items->values());
    }
}
