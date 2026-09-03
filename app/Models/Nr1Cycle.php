<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nr1Cycle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'contract_id',
        'opened_by_contract_version_id',
        'reference_year',
        'status',
        'checklist',
        'notes',
        'migrated_from_client',
        'legacy_client_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'reference_year' => 'integer',
            'checklist' => 'array',
            'migrated_from_client' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function openedByContractVersion(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class, 'opened_by_contract_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checklistCompleto(): bool
    {
        return $this->checklistProgresso() === 100;
    }

    public function checklistProgresso(): int
    {
        $checklist = $this->checklist ?? [];
        $steps = ['etapa1', 'etapa2', 'etapa3', 'etapa4', 'etapa5'];
        $completed = count(array_filter($steps, fn (string $step): bool => ! empty($checklist[$step])));

        return (int) round(($completed / count($steps)) * 100);
    }

    public static function statusFromChecklist(array $checklist): string
    {
        $step1 = ! empty($checklist['etapa1']);
        $step2 = ! empty($checklist['etapa2']);
        $step3 = ! empty($checklist['etapa3']);
        $step4 = ! empty($checklist['etapa4']);
        $step5 = ! empty($checklist['etapa5']);

        return match (true) {
            $step1 && $step2 && $step3 && $step4 && $step5 => 'finalizada',
            $step1 && $step2 && $step3 && $step4 => 'regularizada',
            $step1 && $step2 && $step3 => 'em_andamento',
            default => 'pendente',
        };
    }
}
