<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CustomFieldSection extends Model
{
    use HasTeam;

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'system_defined' => 'boolean',
            'settings' => 'object',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CustomField::class)->orderBy('sort_order');
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }
}
