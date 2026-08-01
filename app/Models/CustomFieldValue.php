<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use App\Observers\CustomFieldValueObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy(CustomFieldValueObserver::class)]
#[Fillable([
    'team_id',
    'entity_type',
    'entity_id',
    'custom_field_id',
    'string_value',
    'text_value',
    'boolean_value',
    'integer_value',
    'float_value',
    'date_value',
    'datetime_value',
    'json_value',
])]
#[WithoutTimestamps]
final class CustomFieldValue extends Model
{
    use HasTeam;

    protected function casts(): array
    {
        return [
            'boolean_value' => 'boolean',
            'json_value' => 'array',
            'date_value' => 'date',
            'datetime_value' => 'datetime',
            'float_value' => 'float',
            'integer_value' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CustomField, $this>
     */
    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function getValue(): mixed
    {
        return match (true) {
            ! is_null($this->string_value) => $this->string_value,
            ! is_null($this->text_value) => $this->text_value,
            ! is_null($this->boolean_value) => $this->boolean_value,
            ! is_null($this->integer_value) => $this->integer_value,
            ! is_null($this->float_value) => $this->float_value,
            ! is_null($this->date_value) => $this->date_value,
            ! is_null($this->datetime_value) => $this->datetime_value,
            ! is_null($this->json_value) => $this->json_value,
            default => null,
        };
    }
}
