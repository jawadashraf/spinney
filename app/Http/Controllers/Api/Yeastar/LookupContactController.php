<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Yeastar;

use App\Http\Middleware\AuthenticateYeastar;
use App\Http\Resources\Yeastar\ContactResource;
use App\Models\Team;
use App\Support\PhoneContacts;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class LookupContactController
{
    /**
     * Match a caller's number to people and companies for contact sync and the screen pop.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        /** @var Team $team */
        $team = $request->attributes->get(AuthenticateYeastar::TEAM_ATTRIBUTE);
        $phone = PhoneNumber::normalize($request->string('phone')->toString());

        if ($phone === null) {
            return ContactResource::collection([]);
        }

        return ContactResource::collection(PhoneContacts::forNumber($team, $phone));
    }
}
