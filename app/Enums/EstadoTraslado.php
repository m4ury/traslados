<?php

namespace App\Enums;

enum EstadoTraslado: string
{
    case Solicitado = 'solicitado';
    case Autorizado = 'autorizado';
    case EnCurso = 'en_curso';
    case Finalizado = 'finalizado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Solicitado => 'Solicitado',
            self::Autorizado => 'Autorizado',
            self::EnCurso => 'En curso',
            self::Finalizado => 'Finalizado',
            self::Cancelado => 'Cancelado',
        };
    }
}
