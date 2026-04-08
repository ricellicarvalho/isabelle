<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tipo_pessoa',
        'cnpj_cpf',
        'razao_social',
        'nome_fantasia',
        'inscricao_estadual',
        'inscricao_municipal',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'telefone',
        'email',
        'contato_nome',
        'contato_telefone',
        'nr1_status',
        'nr1_checklist',
        'portal_user_id',
        'status',
        'observacoes',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'nr1_checklist' => 'array',
        ];
    }

    /**
     * RN07 — Retorna se todos os itens obrigatórios do checklist NR-1 estão concluídos.
     */
    public function nr1ChecklistCompleto(): bool
    {
        $checklist = $this->nr1_checklist ?? [];
        $itens = ['avaliacao', 'devolutiva', 'plano', 'treinamento', 'relatorio'];

        foreach ($itens as $item) {
            if (empty($checklist[$item])) {
                return false;
            }
        }

        return true;
    }

    /**
     * RN07 — Percentual de conclusão do checklist NR-1 (0-100).
     */
    public function nr1ChecklistProgresso(): int
    {
        $checklist = $this->nr1_checklist ?? [];
        $itens = ['avaliacao', 'devolutiva', 'plano', 'treinamento', 'relatorio'];
        $concluidos = count(array_filter($itens, fn ($i) => ! empty($checklist[$i])));

        return (int) round(($concluidos / count($itens)) * 100);
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portal_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function clientDocuments(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
    }
}
