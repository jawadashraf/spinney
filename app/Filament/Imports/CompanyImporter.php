<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Enums\CompanyStatus;
use App\Enums\CompanyVerificationStatus;
use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\CompanyType;
use App\Models\MailingGroup;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports the outreach directory from a spreadsheet: one row per company, with an optional named contact.
 * Rows matching an existing company name are updated, and repeated rows add further contacts.
 */
final class CompanyImporter extends BaseImporter
{
    protected static ?string $model = Company::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Company name')
                ->requiredMapping()
                ->guess(['name', 'company', 'company_name', 'organisation', 'organization'])
                ->rules(['required', 'string', 'max:255'])
                ->example('Leicester Central Masjid'),
            ImportColumn::make('type')
                ->guess(['type', 'category', 'company_type'])
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Masjid')
                ->fillRecordUsing(function (Company $record, ?string $state, CompanyImporter $importer): void {
                    if (blank($state)) {
                        return;
                    }

                    $record->company_type_id = CompanyType::query()
                        ->firstOrCreate(['team_id' => $importer->teamId(), 'name' => Str::of($state)->trim()->toString()])
                        ->getKey();
                }),
            ImportColumn::make('status')
                ->guess(['status', 'active'])
                ->rules(['nullable', 'in:active,inactive,Active,Inactive'])
                ->example('active')
                ->fillRecordUsing(function (Company $record, ?string $state): void {
                    if (filled($state)) {
                        $record->status = CompanyStatus::from(Str::lower($state));
                    }
                }),
            ImportColumn::make('email')
                ->label('General email')
                ->guess(['email', 'email_address', 'general_email'])
                ->rules(['nullable', 'email', 'max:255'])
                ->example('info@example.org'),
            ImportColumn::make('phone')
                ->guess(['phone', 'telephone', 'tel', 'phone_number'])
                ->rules(['nullable', 'string', 'max:50'])
                ->example('0116 000 0000'),
            ImportColumn::make('website')
                ->guess(['website', 'web', 'url'])
                ->rules(['nullable', 'string', 'max:255'])
                ->example('https://example.org'),
            ImportColumn::make('address')
                ->guess(['address', 'street', 'address_line_1'])
                ->rules(['nullable', 'string'])
                ->example('1 High Street, Leicester'),
            ImportColumn::make('postcode')
                ->guess(['postcode', 'post_code', 'zip'])
                ->rules(['nullable', 'string', 'max:20'])
                ->example('LE1 1AA'),
            ImportColumn::make('mailing_groups')
                ->label('Mailing groups')
                ->guess(['mailing_groups', 'groups', 'tags', 'lists'])
                ->rules(['nullable', 'string'])
                ->example('Newsletter, Ramadan appeal')
                ->fillRecordUsing(fn (): null => null),
            ImportColumn::make('verification_status')
                ->label('Verification')
                ->guess(['verification', 'verification_status', 'verified'])
                ->rules(['nullable', 'string'])
                ->example('unverified')
                ->fillRecordUsing(function (Company $record, ?string $state): void {
                    if (filled($state)) {
                        $record->verification_status = CompanyVerificationStatus::tryFrom(Str::of($state)->lower()->trim()->snake()->toString())
                            ?? CompanyVerificationStatus::Unverified;
                    }
                }),
            ImportColumn::make('verification_note')
                ->guess(['verification_note', 'note', 'notes'])
                ->rules(['nullable', 'string'])
                ->example('Postcode differs between sources'),
            ImportColumn::make('source')
                ->guess(['source', 'sources', 'source_url', 'source_urls'])
                ->rules(['nullable', 'string'])
                ->example('https://example.org/directory'),
            ImportColumn::make('part_of')
                ->label('Part of')
                ->guess(['part_of', 'parent', 'parent_company', 'parent_site'])
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Leicester Central Masjid')
                ->fillRecordUsing(function (Company $record, ?string $state, CompanyImporter $importer): void {
                    $importer->linkToParent($record, $state);
                }),
            ImportColumn::make('contact_first_name')
                ->guess(['contact_first_name', 'first_name', 'contact_firstname'])
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Yusuf')
                ->fillRecordUsing(fn (): null => null),
            ImportColumn::make('contact_last_name')
                ->guess(['contact_last_name', 'last_name', 'surname', 'contact_surname'])
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Patel')
                ->fillRecordUsing(fn (): null => null),
            ImportColumn::make('contact_role')
                ->guess(['contact_role', 'role', 'job_title', 'position'])
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Imam')
                ->fillRecordUsing(fn (): null => null),
            ImportColumn::make('contact_email')
                ->guess(['contact_email'])
                ->rules(['nullable', 'email', 'max:255'])
                ->example('imam@example.org')
                ->fillRecordUsing(fn (): null => null),
            ImportColumn::make('contact_phone')
                ->guess(['contact_phone', 'mobile'])
                ->rules(['nullable', 'string', 'max:50'])
                ->example('07700 900000')
                ->fillRecordUsing(fn (): null => null),
        ];
    }

    public function resolveRecord(): Company
    {
        $company = Company::query()
            ->where('team_id', $this->teamId())
            ->where('name', Str::of($this->data['name'] ?? '')->trim()->toString())
            ->first();

        if ($company instanceof Company) {
            return $company;
        }

        $company = new Company;
        $company->setAttribute('team_id', $this->teamId());
        $company->creator_id = $this->import->user_id;
        $company->creation_source = CreationSource::IMPORT;

        return $company;
    }

    public function teamId(): int
    {
        return $this->import->team_id ?? throw new RuntimeException('A company import must belong to a team.');
    }

    /**
     * Link to the named parent company in the same team. A parent that is not in the directory yet is noted
     * instead, so put parents earlier in the file than the sites they run.
     */
    public function linkToParent(Company $company, ?string $parentName): void
    {
        $parentName = Str::of((string) $parentName)->trim()->toString();

        if ($parentName === '' || $parentName === $company->name) {
            return;
        }

        $parent = Company::query()
            ->where('team_id', $this->teamId())
            ->where('name', $parentName)
            ->first();

        if ($parent instanceof Company) {
            $company->parent_company_id = $parent->getKey();

            return;
        }

        $company->verification_note = trim(($company->verification_note ?? '')."\nPart of: {$parentName} (not in the directory when imported).");
    }

    protected function afterSave(): void
    {
        /** @var Company $company */
        $company = $this->record;

        $this->attachMailingGroups($company);
        $this->saveContact($company);
    }

    private function attachMailingGroups(Company $company): void
    {
        $groupIds = Str::of((string) ($this->data['mailing_groups'] ?? ''))
            ->explode(',')
            ->flatMap(fn (string $part): array => explode(';', $part))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->map(fn (string $name): int => MailingGroup::query()->firstOrCreate(['team_id' => $this->teamId(), 'name' => $name])->getKey());

        $company->mailingGroups()->syncWithoutDetaching($groupIds->all());
    }

    private function saveContact(Company $company): void
    {
        $firstName = trim((string) ($this->data['contact_first_name'] ?? ''));
        $lastName = trim((string) ($this->data['contact_last_name'] ?? ''));
        $email = trim((string) ($this->data['contact_email'] ?? ''));

        if ($firstName === '' && $email === '') {
            return;
        }

        $match = $email !== ''
            ? ['email' => $email]
            : ['first_name' => $firstName, 'last_name' => $lastName];

        $contact = $company->contacts()->firstOrNew($match);
        $contact->fill(array_filter([
            'team_id' => $company->team_id,
            'first_name' => $firstName !== '' ? $firstName : $contact->first_name,
            'last_name' => $lastName !== '' ? $lastName : ($contact->last_name ?? $firstName),
            'job_title' => $this->data['contact_role'] ?? null,
            'email' => $email !== '' ? $email : null,
            'phone' => $this->data['contact_phone'] ?? null,
        ], filled(...)));

        if (! $contact->exists) {
            $contact->consent_communications = true;
            $contact->creation_source = CreationSource::IMPORT;
        }

        $contact->save();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your company import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if (($failedRowsCount = $import->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
