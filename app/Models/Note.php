<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\NoteVisibility;
use App\Enums\SupportStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\InvalidatesRelatedAiSummaries;
use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Models\Pivots\Noteable;
use App\Models\Scopes\HideRestrictedNotes;
use App\Observers\NoteObserver;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 * @property SupportStatus|null $support_status
 * @property NoteVisibility $visibility
 */
#[ObservedBy(NoteObserver::class)]
#[ScopedBy(HideRestrictedNotes::class)]
final class Note extends Model implements HasCustomFieldsContract
{
    use HasCreator;
    use HasCustomFields;

    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    use HasTeam;
    use InvalidatesRelatedAiSummaries;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // protected $fillable = [
    //     'team_id',
    //     'creator_id',
    //     'creation_source',
    //     'title',
    // ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
        'visibility' => NoteVisibility::Standard,
    ];

    protected static function booted(): void
    {
        // Only people who may read restricted notes may write one; anyone else's note stays standard.
        self::saving(function (Note $note): void {
            $user = auth()->user();

            if ($note->isRestricted() && $note->isDirty('visibility') && $user instanceof User && ! $user->canSeeRestrictedNotes()) {
                $note->visibility = NoteVisibility::Standard;
            }
        });
    }

    public function isRestricted(): bool
    {
        return $this->visibility === NoteVisibility::Restricted;
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
            'support_status' => SupportStatus::class,
            'visibility' => NoteVisibility::class,
        ];
    }

    /**
     * @return MorphToMany<Company, $this, Noteable>
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'noteable')
            ->using(Noteable::class)
            ->withPivot(['team_id'])
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<People, $this, Noteable>
     */
    public function people(): MorphToMany
    {
        return $this->morphedByMany(People::class, 'noteable')
            ->using(Noteable::class)
            ->withPivot(['team_id'])
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<Opportunity, $this, Noteable>
     */
    public function opportunities(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, 'noteable')
            ->using(Noteable::class)
            ->withPivot(['team_id'])
            ->withTimestamps();
    }
}
