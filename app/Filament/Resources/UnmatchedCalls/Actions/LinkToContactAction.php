<?php

declare(strict_types=1);

namespace App\Filament\Resources\UnmatchedCalls\Actions;

use App\Actions\Calls\LinkPhoneSystemCalls;
use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use App\Models\PbxCallLog;
use App\Models\People;
use App\Models\Team;
use App\Support\PhoneContacts;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

final class LinkToContactAction
{
    public static function make(): Action
    {
        return Action::make('linkToContact')
            ->label('Link to contact')
            ->icon(Heroicon::OutlinedLink)
            ->color('primary')
            ->modalHeading(fn (PbxCallLog $record): string => 'Link calls from '.($record->contact_number ?? 'a withheld number'))
            ->modalDescription('Every unmatched call from this number is linked. Past calls never change a liaison call.')
            ->modalSubmitActionLabel('Link')
            ->schema([
                Select::make('contact')
                    ->label('Person or company')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => self::searchResults($search))
                    ->getOptionLabelUsing(fn (string $value): ?string => ($contact = self::findContact($value)) !== null ? self::label($contact) : null),
                Toggle::make('save_number')
                    ->label('Save this number on their record if they have none, so future calls match')
                    ->default(true)
                    ->visible(fn (PbxCallLog $record): bool => filled($record->contact_number)),
            ])
            ->action(function (array $data, PbxCallLog $record, Action $action): void {
                $contact = self::findContact((string) $data['contact']);

                if ($contact === null) {
                    Notification::make()->title('That contact could not be found')->danger()->send();
                    $action->halt();

                    return;
                }

                $linked = app(LinkPhoneSystemCalls::class)->handle($record, $contact, (bool) ($data['save_number'] ?? false));

                Notification::make()
                    ->title(Str::plural('call', $linked, prependCount: true).' linked to '.self::name($contact))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<string, string>
     */
    private static function searchResults(string $search): array
    {
        if (mb_strlen(mb_trim($search)) < 2) {
            return [];
        }

        return PhoneContacts::search(self::team(), mb_trim($search), 20)
            ->filter(fn (People|Company $contact): bool => ! $contact instanceof Company || CompanyResource::canViewAny())
            ->mapWithKeys(fn (People|Company $contact): array => [PhoneContacts::key($contact) => self::label($contact)])
            ->all();
    }

    /**
     * Companies are only linkable by users who may see the company directory.
     */
    private static function findContact(string $key): People|Company|null
    {
        $contact = PhoneContacts::find(self::team(), $key);

        return $contact instanceof Company && ! CompanyResource::canViewAny() ? null : $contact;
    }

    private static function label(People|Company $contact): string
    {
        $type = match (true) {
            $contact instanceof Company => 'Company',
            $contact->is_service_user => 'Service user',
            default => Str::headline($contact->type ?? 'person'),
        };

        return self::name($contact).' · '.$type.(filled($contact->phone) ? ' · '.$contact->phone : '');
    }

    private static function name(People|Company $contact): string
    {
        return (string) $contact->name;
    }

    private static function team(): Team
    {
        /** @var Team $team */
        $team = Filament::getTenant();

        return $team;
    }
}
