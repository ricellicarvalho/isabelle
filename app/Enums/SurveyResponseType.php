<?php

namespace App\Enums;

enum SurveyResponseType: string
{
    case Numeric = 'numeric_0_10';
    case Qualitative = 'qualitative_5';

    public function label(): string
    {
        return match ($this) {
            self::Numeric => 'Notas de 0 a 10',
            self::Qualitative => 'Péssimo a Excelente',
        };
    }

    public function min(): int
    {
        return $this === self::Numeric ? 0 : 1;
    }

    public function max(): int
    {
        return $this === self::Numeric ? 10 : 5;
    }
}
