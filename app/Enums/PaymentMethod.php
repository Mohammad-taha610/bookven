<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'Cash';
    case Online = 'Online';
}
