<?php

declare(strict_types=1);

namespace App\Http\Resources\Yeastar;

use App\Filament\Resources\Calls\CallResource;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\PeopleResource;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Http\Middleware\AuthenticateYeastar;
use App\Models\Call;
use App\Models\Company;
use App\Models\People;
use App\Models\Team;
use App\Support\PhoneContacts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A person or company as the PBX sees a contact. Only what the screen pop needs is shared, because
 * the PBX copies matched contacts into its own contact list.
 *
 * Build the collection with {@see PhoneContacts}, which loads service users' calls due today.
 *
 * @property People|Company $resource
 */
final class ContactResource extends JsonResource
{
    /**
     * @return array{id: string, first_name: string|null, last_name: string|null, phone: string|null, url: string}
     */
    public function toArray(Request $request): array
    {
        /** @var Team $team */
        $team = $request->attributes->get(AuthenticateYeastar::TEAM_ATTRIBUTE);
        $contact = $this->resource;

        return [
            'id' => PhoneContacts::key($contact),
            'first_name' => $contact instanceof Company ? $contact->name : $contact->first_name,
            'last_name' => $contact instanceof Company ? null : $contact->last_name,
            'phone' => $contact->phone_normalized,
            'url' => $this->screenPopUrl($contact, $team),
        ];
    }

    /**
     * A service user's call due today opens where the liaison records it; everyone else opens their record.
     */
    private function screenPopUrl(People|Company $contact, Team $team): string
    {
        if ($contact instanceof Company) {
            return CompanyResource::getUrl('view', ['record' => $contact], panel: 'app', tenant: $team);
        }

        if (! $contact->is_service_user) {
            return PeopleResource::getUrl('view', ['record' => $contact], panel: 'app', tenant: $team);
        }

        $dueCall = $contact->calls->first();

        if ($dueCall instanceof Call) {
            return CallResource::getUrl('view', ['record' => $dueCall], panel: 'app', tenant: $team);
        }

        return ServiceUserResource::getUrl('view', ['record' => $contact], panel: 'app', tenant: $team);
    }
}
