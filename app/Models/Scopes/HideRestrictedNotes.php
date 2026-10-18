<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\NoteVisibility;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Hides restricted notes from every query unless the signed-in user may read them (AC NT-003). With nobody
 * signed in (queued jobs, exports, AI summaries, console) they are hidden too, so restricted text cannot leak
 * through a background process. Code that genuinely needs them must remove this scope explicitly.
 *
 * @implements Scope<Note>
 */
final class HideRestrictedNotes implements Scope
{
    /**
     * @param  Builder<covariant Note>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if ($user instanceof User && $user->canSeeRestrictedNotes()) {
            return;
        }

        $builder->where($model->qualifyColumn('visibility'), '!=', NoteVisibility::Restricted->value);
    }
}
