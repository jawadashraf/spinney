<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'team_id',
    'summarizable_type',
    'summarizable_id',
    'summary',
    'model_used',
    'prompt_tokens',
    'completion_tokens',
])]
final class AiSummary extends Model
{
    use HasTeam;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function summarizable(): MorphTo
    {
        return $this->morphTo();
    }
}
