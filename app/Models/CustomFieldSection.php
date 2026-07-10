<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'width',
    'code',
    'name',
    'type',
    'entity_type',
    'sort_order',
    'description',
    'active',
    'system_defined',
    'settings',
])]
final class CustomFieldSection extends Model
{
    use HasTeam;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'system_defined' => 'boolean',
            'settings' => 'object',
        ];
    }

    /**
     * @return HasMany<CustomField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(CustomField::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<CustomFieldSection>  $query
     * @return Builder<CustomFieldSection>
     */
    #[Scope]
    protected function forEntity(Builder $query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }
}
