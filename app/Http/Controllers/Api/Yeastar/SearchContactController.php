<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Yeastar;

use App\Http\Middleware\AuthenticateYeastar;
use App\Http\Resources\Yeastar\ContactResource;
use App\Models\Team;
use App\Support\PhoneContacts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class SearchContactController
{
    public const int LIMIT = 20;

    /**
     * Find people and companies by part of their name or number.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        /** @var Team $team */
        $team = $request->attributes->get(AuthenticateYeastar::TEAM_ATTRIBUTE);
        $term = mb_trim($request->string('q')->toString());

        if (mb_strlen($term) < 2) {
            return ContactResource::collection([]);
        }

        return ContactResource::collection(PhoneContacts::search($team, $term, self::LIMIT));
    }
}
