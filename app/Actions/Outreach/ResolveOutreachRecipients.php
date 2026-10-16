<?php

declare(strict_types=1);

namespace App\Actions\Outreach;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\OutreachEmail;
use App\Models\People;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Works out who an outreach email goes to.
 *
 * Companies are included when they are in any selected mailing group, have any selected type, or were
 * picked directly. Inactive companies are never emailed. Each company contributes its general email
 * and/or its contacts on the mailing list, skipping addresses marked invalid or unsubscribed, and each
 * address is emailed once.
 *
 * @phpstan-type Recipient array{company: Company, contact: People|null, email: string, name: string}
 */
final readonly class ResolveOutreachRecipients
{
    /**
     * @return Collection<int, Recipient>
     */
    public function handle(OutreachEmail $email): Collection
    {
        // @phpstan-ignore-next-line Collection type covariance issue
        return $this->forAudience(
            teamId: $email->team_id,
            mailingGroupIds: $email->mailing_group_ids ?? [],
            companyTypeIds: $email->company_type_ids ?? [],
            companyIds: $email->company_ids ?? [],
            toGeneralEmails: $email->send_to_general_emails,
            toContacts: $email->send_to_contacts,
        );
    }

    /**
     * @param  array<int|string>  $mailingGroupIds
     * @param  array<int|string>  $companyTypeIds
     * @param  array<int|string>  $companyIds
     * @return Collection<int, Recipient>
     */
    public function forAudience(
        int $teamId,
        array $mailingGroupIds,
        array $companyTypeIds,
        array $companyIds,
        bool $toGeneralEmails,
        bool $toContacts,
    ): Collection {
        if ($mailingGroupIds === [] && $companyTypeIds === [] && $companyIds === []) {
            return collect();
        }

        $companies = Company::query()
            ->where('team_id', $teamId)
            ->where('status', CompanyStatus::Active)
            ->where(function (Builder $query) use ($mailingGroupIds, $companyTypeIds, $companyIds): void {
                $query
                    ->when($mailingGroupIds !== [], fn (Builder $query) => $query->orWhereHas(
                        'mailingGroups',
                        fn (Builder $query) => $query->whereKey($mailingGroupIds),
                    ))
                    ->when($companyTypeIds !== [], fn (Builder $query) => $query->orWhereIn('company_type_id', $companyTypeIds))
                    ->when($companyIds !== [], fn (Builder $query) => $query->orWhereIn('companies.id', $companyIds));
            })
            ->when($toContacts, fn (Builder $query) => $query->with(['contacts' => fn ($query) => $query
                ->where('consent_communications', true)
                ->whereNotNull('email')
                ->whereNull('email_invalid_at')
                ->whereNull('unsubscribed_at')
                ->orderBy('first_name'),
            ]))
            ->orderBy('name')
            ->get();

        /** @var Collection<int, Recipient> $recipients */
        $recipients = collect();

        foreach ($companies as $company) {
            if ($toGeneralEmails && filled($company->email) && $company->email_invalid_at === null && $company->unsubscribed_at === null) {
                $recipients->push(['company' => $company, 'contact' => null, 'email' => $company->email, 'name' => $company->name]);
            }

            if ($toContacts) {
                foreach ($company->contacts as $contact) {
                    $recipients->push(['company' => $company, 'contact' => $contact, 'email' => (string) $contact->email, 'name' => $contact->name]);
                }
            }
        }

        // @phpstan-ignore-next-line Collection type covariance issue
        return $recipients
            ->filter(fn (array $recipient): bool => filter_var($recipient['email'], FILTER_VALIDATE_EMAIL) !== false)
            ->unique(fn (array $recipient): string => Str::lower($recipient['email']))
            ->values();
    }
}
