<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutreachEmails\Schemas;

use App\Actions\Outreach\BuildOutreachMail;
use App\Actions\Outreach\ResolveOutreachRecipients;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyType;
use App\Models\MailingGroup;
use App\Support\MailQuota;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use FinityLabs\FinMail\FinMailPlugin;
use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Settings\AttachmentSettings;
use Illuminate\Database\Eloquent\Builder;

final class OutreachEmailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Recipients')
                    ->description('Companies in any selected group or type, plus any picked by name. Inactive companies, invalid addresses and unsubscribed addresses are skipped.')
                    ->schema([
                        Select::make('mailing_group_ids')
                            ->label('Mailing groups')
                            ->multiple()
                            ->options(fn (): array => MailingGroup::query()->where('team_id', self::teamId())->orderBy('name')->pluck('name', 'id')->all())
                            ->live(),
                        Select::make('company_type_ids')
                            ->label('Company types')
                            ->multiple()
                            ->options(fn (): array => CompanyType::query()->where('team_id', self::teamId())->orderBy('name')->pluck('name', 'id')->all())
                            ->live(),
                        Select::make('company_ids')
                            ->label('Specific companies')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => self::activeCompanies()
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => self::activeCompanies()
                                ->whereKey($values)
                                ->pluck('name', 'id')
                                ->all())
                            ->live()
                            ->columnSpanFull(),
                        Toggle::make('send_to_general_emails')
                            ->label('Send to general emails')
                            ->default(true)
                            ->live(),
                        Toggle::make('send_to_contacts')
                            ->label('Send to contacts on the mailing list')
                            ->default(true)
                            ->live(),
                        TextEntry::make('audience')
                            ->hiddenLabel()
                            ->state(fn (Get $get): string => self::audienceSummary($get))
                            ->color('primary')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Email')
                    ->schema([
                        Select::make('template_key')
                            ->label('Start from template')
                            ->options(fn (): array => EmailTemplate::query()->active()->get()->mapWithKeys(
                                fn (EmailTemplate $template): array => [$template->key => $template->name],
                            )->all())
                            ->default(config('outreach.template_key'))
                            ->helperText('Choosing a template replaces the subject and message below.')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $template = $state !== null ? EmailTemplate::findByKey($state) : null;

                                if ($template !== null) {
                                    $set('subject', $template->subject);
                                    $set('body', $template->body);
                                }
                            }),
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->default(fn (): ?string => EmailTemplate::findByKey((string) config('outreach.template_key'))?->subject)
                            ->helperText('You can use {{ company.name }} or {{ recipient.name }} here too.'),
                        RichEditor::make('body')
                            ->label('Message')
                            ->required()
                            ->default(fn (): ?string => EmailTemplate::findByKey((string) config('outreach.template_key'))?->body)
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'link'],
                                ['h2', 'h3', 'bulletList', 'orderedList', 'blockquote'],
                                ['attachFiles', 'customBlocks', 'mergeTags'],
                                ['undo', 'redo'],
                            ])
                            ->mergeTags(BuildOutreachMail::MERGE_TAGS)
                            ->customBlocks(self::customBlocks())
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('outreach-images')
                            ->fileAttachmentsVisibility('public')
                            ->helperText('Images are stored publicly so they show in recipients\' inboxes. An unsubscribe link is added to every email.')
                            ->columnSpanFull(),
                        FileUpload::make('attachments')
                            ->multiple()
                            ->disk((string) config('fin-mail.attachments_disk', 'local'))
                            ->directory('outreach-attachments')
                            ->visibility('private')
                            ->storeFileNamesIn('attachment_names')
                            ->maxSize(fn (): int => app(AttachmentSettings::class)->max_size_mb * 1024)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    private static function audienceSummary(Get $get): string
    {
        $recipients = app(ResolveOutreachRecipients::class)->forAudience(
            teamId: self::teamId(),
            mailingGroupIds: $get('mailing_group_ids') ?? [],
            companyTypeIds: $get('company_type_ids') ?? [],
            companyIds: $get('company_ids') ?? [],
            toGeneralEmails: (bool) $get('send_to_general_emails'),
            toContacts: (bool) $get('send_to_contacts'),
        );

        $companies = $recipients->pluck('company.id')->unique()->count();
        $available = app(MailQuota::class)->availableForOutreach();
        $warning = $recipients->count() > $available ? ' This is more than can be sent right now.' : '';

        return "Will be sent to {$recipients->count()} addresses at {$companies} companies. {$available} sends available for outreach in the next 24 hours.{$warning}";
    }

    /**
     * @return Builder<Company>
     */
    private static function activeCompanies(): Builder
    {
        return Company::query()
            ->where('team_id', self::teamId())
            ->where('status', CompanyStatus::Active)
            ->orderBy('name');
    }

    /**
     * Fin Mail's email blocks, such as its call-to-action button.
     *
     * @return array<class-string<RichContentCustomBlock>>
     */
    private static function customBlocks(): array
    {
        /** @var array<class-string<RichContentCustomBlock>> */
        return FinMailPlugin::getCustomBlockClasses();
    }

    private static function teamId(): int
    {
        return (int) Filament::getTenant()?->getKey();
    }
}
