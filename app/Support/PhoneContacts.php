<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Call;
use App\Models\Company;
use App\Models\People;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * The records the phone system can recognise: people of every type (service users first) and companies.
 *
 * People and companies are numbered separately, so the PBX gets a prefixed contact id
 * ("person-12", "company-5") that is unique across both and comes back on the call journal.
 * A number nobody recognises gets an "unknown-…" id, which is never stored as a contact.
 */
final class PhoneContacts
{
    public const string PERSON_PREFIX = 'person-';

    public const string COMPANY_PREFIX = 'company-';

    public const string UNKNOWN_PREFIX = 'unknown-';

    public static function key(People|Company $contact): string
    {
        return ($contact instanceof Company ? self::COMPANY_PREFIX : self::PERSON_PREFIX).$contact->getKey();
    }

    /**
     * The id the PBX keeps for a caller who is not in Spinney, so it can journal their calls.
     */
    public static function unknownKey(?string $normalizedNumber): string
    {
        return self::UNKNOWN_PREFIX.(mb_ltrim((string) $normalizedNumber, '+') ?: 'withheld');
    }

    /**
     * Everyone whose number matches, service users first, then other people, then companies.
     *
     * @return Collection<int, People|Company>
     */
    public static function forNumber(Team $team, string $normalizedNumber): Collection
    {
        $people = People::query()
            ->where('team_id', $team->id)
            ->where('phone_normalized', $normalizedNumber)
            ->orderByDesc('is_service_user')
            ->orderBy('id')
            ->get();

        $companies = Company::query()
            ->where('team_id', $team->id)
            ->where('phone_normalized', $normalizedNumber)
            ->orderBy('id')
            ->get();

        return self::merge($people, $companies);
    }

    /**
     * People and companies matching part of a name or number, service users first.
     *
     * @return Collection<int, People|Company>
     */
    public static function search(Team $team, string $term, int $limit): Collection
    {
        $phoneFragment = PhoneNumber::searchFragment($term);
        $nameTerm = '%'.addcslashes($term, '\\%_').'%';

        $people = People::query()
            ->where('team_id', $team->id)
            ->where(function (Builder $query) use ($nameTerm, $phoneFragment): void {
                $query->where('name', 'like', $nameTerm)
                    ->orWhere('first_name', 'like', $nameTerm)
                    ->orWhere('last_name', 'like', $nameTerm)
                    ->when($phoneFragment !== null, fn (Builder $query): Builder => $query->orWhere('phone_normalized', 'like', '%'.$phoneFragment.'%'));
            })
            ->orderByDesc('is_service_user')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get();

        $companies = Company::query()
            ->where('team_id', $team->id)
            ->where(function (Builder $query) use ($nameTerm, $phoneFragment): void {
                $query->where('name', 'like', $nameTerm)
                    ->when($phoneFragment !== null, fn (Builder $query): Builder => $query->orWhere('phone_normalized', 'like', '%'.$phoneFragment.'%'));
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return self::merge($people, $companies)->take($limit)->values();
    }

    /**
     * Resolve the contact id the PBX sends back on the call journal. Bare numbers are people,
     * as synced by the first version of the template.
     */
    public static function find(Team $team, ?string $contactId): People|Company|null
    {
        if ($contactId === null || $contactId === '' || str_starts_with($contactId, self::UNKNOWN_PREFIX)) {
            return null;
        }

        if (str_starts_with($contactId, self::COMPANY_PREFIX)) {
            $id = mb_substr($contactId, mb_strlen(self::COMPANY_PREFIX));

            return ctype_digit($id) ? Company::query()->where('team_id', $team->id)->find((int) $id) : null;
        }

        $id = str_starts_with($contactId, self::PERSON_PREFIX) ? mb_substr($contactId, mb_strlen(self::PERSON_PREFIX)) : $contactId;

        return ctype_digit($id) ? People::query()->where('team_id', $team->id)->find((int) $id) : null;
    }

    /**
     * @param  EloquentCollection<int, People>  $people
     * @param  EloquentCollection<int, Company>  $companies
     * @return Collection<int, People|Company>
     */
    private static function merge(EloquentCollection $people, EloquentCollection $companies): Collection
    {
        // Service users' calls due today decide where their screen pop opens.
        $people->load(['calls' => function (Relation $calls): void {
            /** @var HasMany<Call, People> $calls */
            $calls->dueByEndOfUkDay()->orderBy('calls.due_at');
        }]);

        return collect([...$people->all(), ...$companies->all()]);
    }
}
