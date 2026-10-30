<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ClientDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'nr1_cycle_id',
        'titulo',
        'tipo',
        'caminho_arquivo',
        'descricao',
        'visivel_portal',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'visivel_portal' => 'boolean',
            'caminho_arquivo' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ClientDocument $document): void {
            if ($document->tipo !== 'devolutiva_nr1') {
                $document->nr1_cycle_id = null;

                return;
            }

            $cycleBelongsToClient = Nr1Cycle::query()
                ->whereKey($document->nr1_cycle_id)
                ->where('client_id', $document->client_id)
                ->exists();

            if (! $cycleBelongsToClient) {
                throw ValidationException::withMessages([
                    'nr1_cycle_id' => 'Selecione um ciclo NR-1 pertencente a este cliente.',
                ]);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function nr1Cycle(): BelongsTo
    {
        return $this->belongsTo(Nr1Cycle::class);
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
