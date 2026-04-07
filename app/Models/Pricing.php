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
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'custo_direto' => 'decimal:2',
            'custo_indireto' => 'decimal:2',
            'margem_lucro' => 'decimal:2',
            'preco_venda' => 'decimal:2',
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
