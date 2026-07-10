<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\CustomFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'custom_field_section_id',
    'width',
    'code',
    'name',
    'type',
    'lookup_type',
    'entity_type',
    'sort_order',
    'validation_rules',
    'active',
    'system_defined',
    'settings',
])]
final class CustomField extends Model
{
    /** @use HasFactory<CustomFieldFactory> */
    use HasFactory;

    use HasTeam;

    protected function casts(): array
    {
        return [
            'validation_rules' => 'array',
            'active' => 'boolean',
            'system_defined' => 'boolean',
            'settings' => 'object',
        ];
    }

    /**
     * @return BelongsTo<CustomFieldSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CustomFieldSection::class, 'custom_field_section_id');
    }

    /**
     * @return HasMany<CustomFieldOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<CustomFieldValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /**
     * @param  Builder<CustomField>  $query
     * @return Builder<CustomField>
     */
    #[Scope]
    protected function forEntity(Builder $query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function getValueColumn(): string
    {
        return match ($this->type) {
            'text', 'string', 'url', 'email' => 'string_value',
            'longText', 'richtext', 'textarea' => 'text_value',
            'boolean', 'toggle' => 'boolean_value',
            'integer', 'number', 'select' => 'integer_value',
            'float', 'decimal' => 'float_value',
            'date' => 'date_value',
            'datetime' => 'datetime_value',
            'json', 'tags' => 'json_value',
            default => 'string_value',
        };
    }
}
