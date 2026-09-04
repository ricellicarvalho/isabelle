<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use SolutionForest\FilamentTree\Concern\ModelTree;

class Category extends Model
{
    use HasFactory, ModelTree, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            if (! $category->isDirty('parent_id') || blank($category->parent_id)) {
                return;
            }

            $parentId = (int) $category->parent_id;
            $visited = [];

            while ($parentId > 0) {
                if ($parentId === (int) $category->getKey() || isset($visited[$parentId])) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Uma categoria não pode ser conta pai dela mesma nem de uma conta ancestral.',
                    ]);
                }

                $visited[$parentId] = true;
                $parentId = (int) (static::withTrashed()->whereKey($parentId)->value('parent_id') ?? 0);
            }
        });
    }

    public static function defaultParentKey()
    {
        return null;
    }

    public function scopeIsRoot(Builder $query)
    {
        return $query->whereNull($this->determineParentColumnName());
    }

    public function getHighestOrderNumber(): int
    {
        $parent = $this->{$this->determineParentColumnName()};
        $query = static::query();
        if ($parent === null) {
            $query->whereNull($this->determineParentColumnName());
        } else {
            $query->where($this->determineParentColumnName(), $parent);
        }

        return (int) $query->max($this->determineOrderColumnName());
    }

    protected $fillable = [
        'parent_id',
        'codigo',
        'descricao',
        'tipo',
        'order',
        'ativo',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function getCodigoCompletoAttribute(): string
    {
        return trim(($this->parent ? $this->parent->codigo_completo . ' › ' : '') . $this->descricao);
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

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function pricings(): HasMany
    {
        return $this->hasMany(Pricing::class);
    }
}
