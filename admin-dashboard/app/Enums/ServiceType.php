<?php

namespace App\Enums;

enum ServiceType: string
{
    case InStore = 'in_store';
    case HomeCare = 'home_care';

    public function label(): string
    {
        return match ($this) {
            self::InStore => 'In-Store',
            self::HomeCare => 'Home Care',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
