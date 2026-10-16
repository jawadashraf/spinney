<?php

declare(strict_types=1);

namespace App\Http\Resources\Yeastar;

use App\Filament\Resources\Calls\CallResource;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Http\Middleware\AuthenticateYeastar;
use App\Models\Call;
use App\Models\ServiceUser;
use App\Models\Team;
use Closure;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A service user as the PBX sees a contact. Only what the screen pop needs is shared, because the
 * PBX copies matched contacts into its own contact list.
 *
 * Load with {@see self::eagerLoads()}.
 *
 * @mixin ServiceUser
 */
final class ContactResource extends JsonResource
{
    /**
     * @return array{id: string, first_name: string|null, last_name: string|null, phone: string|null, url: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone_normalized,
            'url' => $this->screenPopUrl($request->attributes->get(AuthenticateYeastar::TEAM_ATTRIBUTE)),
        ];
    }

    /**
     * Relations the resource reads: the service user's open calls due today, earliest first.
     *
     * @return array<string, Closure(Relation<*, *, *>): void>
     */
    public static function eagerLoads(): array
    {
        return ['calls' => function (Relation $calls): void {
            /** @var HasMany<Call, ServiceUser> $calls */
            $calls->dueByEndOfUkDay()->orderBy('calls.due_at');
        }];
    }

    /**
     * Open the call that is due today, so the liaison lands where they record it; otherwise the service user.
     */
    private function screenPopUrl(Team $team): string
    {
        $dueCall = $this->calls->first();

        if ($dueCall instanceof Call) {
            return CallResource::getUrl('view', ['record' => $dueCall], panel: 'app', tenant: $team);
        }

        return ServiceUserResource::getUrl('edit', ['record' => $this->resource], panel: 'app', tenant: $team);
    }
}
