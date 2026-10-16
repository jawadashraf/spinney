<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Yeastar;

use App\Http\Middleware\AuthenticateYeastar;
use App\Http\Resources\Yeastar\ContactResource;
use App\Models\ServiceUser;
use App\Models\Team;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class SearchContactController
{
    public const int LIMIT = 20;

    /**
     * Find service users by part of their name or number.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        /** @var Team $team */
        $team = $request->attributes->get(AuthenticateYeastar::TEAM_ATTRIBUTE);
        $term = mb_trim($request->string('q')->toString());

        if (mb_strlen($term) < 2) {
            return ContactResource::collection([]);
        }

        $phoneFragment = PhoneNumber::searchFragment($term);
        $nameTerm = '%'.addcslashes($term, '\\%_').'%';

        $serviceUsers = ServiceUser::query()
            ->where('team_id', $team->id)
            ->where(function (Builder $query) use ($nameTerm, $phoneFragment): void {
                $query->where('name', 'like', $nameTerm)
                    ->orWhere('first_name', 'like', $nameTerm)
                    ->orWhere('last_name', 'like', $nameTerm)
                    ->when($phoneFragment !== null, fn (Builder $query): Builder => $query->orWhere('phone_normalized', 'like', '%'.$phoneFragment.'%'));
            })
            ->with(ContactResource::eagerLoads())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(self::LIMIT)
            ->get();

        return ContactResource::collection($serviceUsers);
    }
}
