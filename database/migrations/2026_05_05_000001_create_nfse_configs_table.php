<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfse_configs', function (Blueprint $table) {
            $table->id();

            // Identificação do Prestador
            $table->string('cnpj', 14)->comment('CNPJ sem formatação');
            $table->string('inscricao_municipal', 20);
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('email');
            $table->string('telefone', 20)->nullable();

            // Endereço
            $table->string('endereco');
            $table->string('numero', 20);
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100);
            $table->string('municipio_ibge', 7)->comment('Código IBGE 7 dígitos (ex: 1709500 = Gurupi-TO)');
            $table->string('nome_municipio', 100);
            $table->char('uf', 2);
            $table->char('codigo_uf', 2)->comment('Código numérico da UF (ex: 17 para TO)');
            $table->string('cep', 8);

            // Tributação
            $table->enum('simples_nacional', ['1', '2'])->default('2')->comment('1=Simples Nacional, 2=Outros');
            $table->unsignedTinyInteger('regime_especial_tributacao')->nullable()->comment('Código do regime especial (1-9)');
            $table->boolean('padrao_nacional')->default(true)->comment('true = padrão nacional NFSe');

            // RPS
            $table->string('serie_rps', 5)->default('RPS');
            $table->unsignedBigInteger('proximo_numero_rps')->default(1)->comment('Contador atômico do número RPS');

            // ISS Padrão (pode ser sobrescrito por NfseServiceCode)
            $table->decimal('aliquota_iss_padrao', 5, 2)->default(2.00);
            $table->string('item_lista_servico', 10)->default('17.01')->comment('LC 116/2003');
            $table->string('codigo_tributacao_municipio', 20)->nullable();
            $table->string('codigo_cnae', 10)->nullable();
            $table->enum('exigibilidade_iss', ['1', '2', '3', '4', '5', '6', '7'])->default('1')
                ->comment('1=Exigível, 2=Não incidência, 3=Isenção, 4=Exportação, 5=Imunidade, 6=Susp.Judicial, 7=Susp.Administrativa');
            $table->enum('iss_retido', ['1', '2'])->default('2')->comment('1=Retido pelo tomador, 2=Não retido');

            $table->boolean('ativo')->default(true);

            // Auditoria
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfse_configs');
    }
};
