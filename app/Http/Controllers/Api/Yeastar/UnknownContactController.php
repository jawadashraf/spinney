<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Yeastar;

use App\Http\Middleware\AuthenticateYeastar;
use App\Http\Requests\Yeastar\UnknownContactRequest;
use App\Models\Team;
use App\Support\PhoneContacts;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;

final readonly class UnknownContactController
{
    /**
     * Answer the PBX's CreateNewContact request without creating anything, so it still journals calls
     * from numbers nobody recognises. Those calls appear under Unmatched calls in Spinney.
     */
    public function __invoke(UnknownContactRequest $request): JsonResponse
    {
        /** @var Team $team */
        $team = $request->attributes->get(AuthenticateYeastar::TEAM_ATTRIBUTE);
        $phone = PhoneNumber::normalize($request->string('phone')->toString());

        // The number may have been added to a record since the PBX last looked it up.
        $contact = $phone !== null ? PhoneContacts::forNumber($team, $phone)->first() : null;

        return response()->json([
            'id' => $contact !== null ? PhoneContacts::key($contact) : PhoneContacts::unknownKey($phone),
        ]);
    }
}
