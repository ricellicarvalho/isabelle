<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricings', function (Blueprint $table) {
            // Serviços da calculadora NR-1
            $table->unsignedInteger('num_funcionarios')->nullable()->after('descricao')
                ->comment('Quantidade de funcionários (APLICAÇÃO)');
            $table->decimal('valor_por_funcionario', 15, 2)->default(100.00)->after('num_funcionarios')
                ->comment('Custo por funcionário na APLICAÇÃO');
            $table->decimal('despesa_encontro', 15, 2)->default(1000.00)->after('valor_por_funcionario');
            $table->decimal('despesa_risco', 15, 2)->default(200.00)->after('despesa_encontro');
            $table->decimal('despesa_relatorio', 15, 2)->default(200.00)->after('despesa_risco');
            $table->decimal('despesa_acao_anual', 15, 2)->default(200.00)->after('despesa_relatorio');
            $table->decimal('despesas_indiretas', 15, 2)->default(0.00)->after('despesa_acao_anual')
                ->comment('Despesas indiretas gerais (sem lucro)');
            $table->string('deslocamento')->nullable()->after('despesas_indiretas')
                ->comment('Campo informativo — não entra nos cálculos');

            // Parâmetros de precificação
            $table->decimal('percentual_imposto', 5, 2)->default(8.00)->after('margem_lucro')
                ->comment('% de imposto/NFSe aplicado sobre o total s/imposto');
            $table->unsignedInteger('quantidade_parcelas')->default(1)->after('percentual_imposto');
        });
    }

    public function down(): void
    {
        Schema::table('pricings', function (Blueprint $table) {
            $table->dropColumn([
                'num_funcionarios',
                'valor_por_funcionario',
                'despesa_encontro',
                'despesa_risco',
                'despesa_relatorio',
                'despesa_acao_anual',
                'despesas_indiretas',
                'deslocamento',
                'percentual_imposto',
                'quantidade_parcelas',
            ]);
        });
    }
};
