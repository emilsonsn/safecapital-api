<?php

namespace App\Enums;

enum PropertyTypeEnum: string
{
    case Residential = 'Residential';
    case Commercial = 'Commercial';    

    public function label(): string
    {
        return match ($this) {
            self::Residential => 'Residencial',
            self::Commercial => 'Comercial',
        };
    }
}