<?php

namespace Tests\Unit;

use App\Support\FiscalDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FiscalDocumentTest extends TestCase
{
    #[DataProvider('validDocuments')]
    public function test_validates_and_formats_documents(string $document, string $type, string $cnab): void
    {
        $this->assertTrue(FiscalDocument::isValid($document));
        $this->assertSame($type, FiscalDocument::type($document));
        $this->assertSame($cnab, FiscalDocument::cnabNumber($document));
    }

    public static function validDocuments(): array
    {
        return [
            'CPF' => ['615.674.340-53', FiscalDocument::CPF, '00061567434053'],
            'CNPJ' => ['12.547.355/0001-84', FiscalDocument::CNPJ, '12547355000184'],
        ];
    }

    #[DataProvider('invalidDocuments')]
    public function test_rejects_invalid_documents(string $document): void
    {
        $this->assertFalse(FiscalDocument::isValid($document));
    }

    public static function invalidDocuments(): array
    {
        return [
            [''],
            ['123'],
            ['111.111.111-11'],
            ['12.345.678/0001-90'],
        ];
    }

    public function test_caepf_accepts_only_a_valid_cpf_or_cnpj(): void
    {
        $this->assertTrue(FiscalDocument::isCompatibleWithPersonType('615.674.340-53', 'caepf'));
        $this->assertTrue(FiscalDocument::isCompatibleWithPersonType('12.547.355/0001-84', 'caepf'));
        $this->assertFalse(FiscalDocument::isCompatibleWithPersonType('CAEPF 123', 'caepf'));
    }
}
