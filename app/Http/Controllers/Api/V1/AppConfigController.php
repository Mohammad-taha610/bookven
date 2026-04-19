<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class AppConfigController extends Controller
{
    public function show()
    {
        return $this->jsonSuccess([
            'app_name' => config('app.name'),
            'api_version' => 'v1',
            'min_supported_mobile_version' => null,
            'currency_code' => 'PKR',
            'currency_symbol' => 'Rs.',
        ]);
    }
}
