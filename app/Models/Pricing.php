<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pricing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'nome',
        'descricao',
        'custo_direto',
        'custo_indireto',
        'margem_lucro',
        'preco_venda',
        'observacoes',
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
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'custo_direto'          => 'decimal:2',
            'custo_indireto'        => 'decimal:2',
            'margem_lucro'          => 'decimal:2',
            'preco_venda'           => 'decimal:2',
            'valor_por_funcionario' => 'decimal:2',
            'despesa_encontro'      => 'decimal:2',
            'despesa_risco'         => 'decimal:2',
            'despesa_relatorio'     => 'decimal:2',
            'despesa_acao_anual'    => 'decimal:2',
            'despesas_indiretas'    => 'decimal:2',
            'deslocamento'          => 'decimal:2',
            'percentual_imposto'    => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
