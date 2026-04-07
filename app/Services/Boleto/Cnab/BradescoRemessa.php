<?php

namespace App\Services\Boleto\Cnab;

use Eduardokum\LaravelBoleto\Cnab\Remessa\Banco\Bradesco as LibBradescoCnab;

/**
 * Subclasse local do CNAB Bradesco do pacote eduardokum/laravel-boleto.
 *
 * O método `addDetalhe` da versão 0.1 invoca `getAcencia()` (typo) que cai no
 * `__call` do AbstractCnab procurando a propriedade `acencia`. Declaramos a
 * propriedade aqui para evitar a `Exception("Método acencia não existe")` e a
 * deprecation de criação de propriedade dinâmica em PHP 8.2+.
 */
class BradescoRemessa extends LibBradescoCnab
{
    public $acencia;
}
