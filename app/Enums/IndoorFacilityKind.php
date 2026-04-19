<?php

namespace App\Enums;

enum IndoorFacilityKind: string
{
    case Court = 'court';
    case Net = 'net';

    public function label(): string
    {
        return match ($this) {
            self::Court => 'Court',
            self::Net => 'Net',
        };
    }

    public function iconKey(): string
    {
        return $this->value;
    }
}
