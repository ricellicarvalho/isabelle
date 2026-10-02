<?php

namespace App\Support;

final class FiscalDocument
{
    public const CPF = 'cpf';

    public const CNPJ = 'cnpj';

    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', $value ?? '') ?? '';
    }

    public static function type(?string $value): ?string
    {
        return match (strlen(self::digits($value))) {
            11 => self::CPF,
            14 => self::CNPJ,
            default => null,
        };
    }

    public static function isValid(?string $value): bool
    {
        $digits = self::digits($value);

        return match (strlen($digits)) {
            11 => self::isValidCpf($digits),
            14 => self::isValidCnpj($digits),
            default => false,
        };
    }

    public static function isCompatibleWithPersonType(?string $value, ?string $personType): bool
    {
        $documentType = self::type($value);

        return match ($personType) {
            'pf' => $documentType === self::CPF,
            'pj' => $documentType === self::CNPJ,
            'caepf' => in_array($documentType, [self::CPF, self::CNPJ], true),
            default => false,
        };
    }

    public static function cnabType(?string $value): ?string
    {
        return match (self::type($value)) {
            self::CPF => '01',
            self::CNPJ => '02',
            default => null,
        };
    }

    public static function cnabNumber(?string $value): ?string
    {
        $digits = self::digits($value);

        return self::type($digits) ? str_pad($digits, 14, '0', STR_PAD_LEFT) : null;
    }

    private static function isValidCpf(string $cpf): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;
            for ($index = 0; $index < $position; $index++) {
                $sum += (int) $cpf[$index] * (($position + 1) - $index);
            }

            $digit = ((10 * $sum) % 11) % 10;
            if ($digit !== (int) $cpf[$position]) {
                return false;
            }
        }

        return true;
    }

    private static function isValidCnpj(string $cnpj): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([12, 13] as $position) {
            $sum = 0;
            $weight = $position - 7;

            for ($index = 0; $index < $position; $index++) {
                $sum += (int) $cnpj[$index] * $weight--;
                if ($weight < 2) {
                    $weight = 9;
                }
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;
            if ($digit !== (int) $cnpj[$position]) {
                return false;
            }
        }

        return true;
    }
}
