<?php

namespace App\Enums;

enum TipoTraslado: string
{
    case Spontaneous = 'spontaneous';
    case Scheduled = 'scheduled';

    public function label(): string
    {
        return match ($this) {
            self::Spontaneous => 'Espontáneo',
            self::Scheduled => 'Programado',
        };
    }
}
