<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;

final class CustomField extends Model
{
    use HasFactory;
    use HasTeam;

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'validation_rules' => 'array',
            'active' => 'boolean',
            'system_defined' => 'boolean',
            'settings' => 'object',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CustomFieldSection::class, 'custom_field_section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class)->orderBy('sort_order');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forEntity($query, string $entityType)
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
