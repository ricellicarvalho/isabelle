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
        'telefones',
        'nr1_status',
        'nr1_checklist',
        'portal_user_id',
        'status',
        'observacoes',
        'cadastro_token',
        'cadastro_token_expira_em',
        'cadastro_preenchido',
        'municipio_ibge',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'nr1_checklist'            => 'array',
            'telefones'                => 'array',
            'cadastro_token_expira_em' => 'datetime',
            'cadastro_preenchido'      => 'boolean',
        ];
    }

    /**
     * RN07 — Retorna se todas as 5 etapas NR-1 estão concluídas.
     */
    public function nr1ChecklistCompleto(): bool
    {
        $checklist = $this->nr1_checklist ?? [];
        $etapas = ['etapa1', 'etapa2', 'etapa3', 'etapa4', 'etapa5'];

        foreach ($etapas as $etapa) {
            if (empty($checklist[$etapa])) {
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
        $etapas = ['etapa1', 'etapa2', 'etapa3', 'etapa4', 'etapa5'];
        $concluidos = count(array_filter($etapas, fn ($e) => ! empty($checklist[$e])));

        return (int) round(($concluidos / count($etapas)) * 100);
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

    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class);
    }

    public function cadastroTokenValido(): bool
    {
        return filled($this->cadastro_token)
            && (! $this->cadastro_token_expira_em || $this->cadastro_token_expira_em->isFuture());
    }
}
