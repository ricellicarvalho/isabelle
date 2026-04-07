<?php

/**
 * Polyfills para funções removidas no PHP 8 mas ainda referenciadas pelo
 * pacote itbz/fpdf (transitivamente via eduardokum/laravel-boleto v0.1).
 *
 * Definidas no namespace global — o pacote chama as funções sem prefixo, o
 * que faz o PHP procurar primeiro no namespace corrente e cair no global.
 */
if (! function_exists('get_magic_quotes_runtime')) {
    function get_magic_quotes_runtime(): bool
    {
        return false;
    }
}

if (! function_exists('set_magic_quotes_runtime')) {
    function set_magic_quotes_runtime(bool $new_setting): bool
    {
        return true;
    }
}
