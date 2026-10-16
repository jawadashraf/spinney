<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutreachEmails\Pages;

use App\Actions\Outreach\BuildOutreachMail;
use App\Actions\Outreach\ResolveOutreachRecipients;
use App\Actions\Outreach\SendOutreachEmail;
use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\OutreachEmails\OutreachEmailResource;
use App\Models\OutreachEmail;
use App\Models\User;
use App\Support\MailQuota;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property OutreachEmail $record
 */
final class ViewOutreachEmail extends ViewRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = OutreachEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->sendAction(),
            EditAction::make(),
            ActionGroup::make([
                $this->sendTestAction(),
                $this->saveAsTemplateAction(),
                $this->duplicateAction(),
            ]),
        ];
    }

    private function sendAction(): Action
    {
        return Action::make('send')
            ->label('Send')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->authorize('send', $this->record)
            ->requiresConfirmation()
            ->modalHeading('Send this email?')
            ->modalDescription(fn (): string => sprintf(
                'It will be sent to %d addresses and cannot be recalled. %s',
                app(ResolveOutreachRecipients::class)->handle($this->record)->count(),
                app(MailQuota::class)->summary(),
            ))
            ->modalSubmitActionLabel('Send now')
            ->action(function (SendOutreachEmail $send): void {
                try {
                    $count = $send->handle($this->record, $this->currentUser());
                } catch (ValidationException $exception) {
                    Notification::make()->danger()->title('Not sent')->body(Arr::first($exception->errors())[0] ?? null)->send();

                    return;
                }

                Notification::make()->success()->title("Sending to {$count} addresses")->body('Progress shows in the recipients list below.')->send();
                $this->refreshFormData(['status', 'sent_at']);
            });
    }

    private function sendTestAction(): Action
    {
        return Action::make('sendTest')
            ->label('Send a test to me')
            ->icon('heroicon-o-beaker')
            ->authorize('send', $this->record)
            ->action(function (BuildOutreachMail $buildMail, ResolveOutreachRecipients $resolveRecipients, MailQuota $quota): void {
                $sample = $resolveRecipients->handle($this->record)->first();

                if ($sample === null) {
                    Notification::make()->warning()->title('Choose recipients first')->body('The test is personalised as the first recipient.')->send();

                    return;
                }

                if ($quota->availableForOutreach() < 1) {
                    Notification::make()->danger()->title('Daily sending limit reached')->body($quota->summary())->send();

                    return;
                }

                $user = $this->currentUser();
                $mail = $buildMail->handle($this->record, $sample['company'], $sample['contact'], $sample['name'], null);
                $mail->overrideSubject('[Test] '.$this->record->subject);

                Mail::to($user->email, $user->name)->sendNow($mail);

                Notification::make()->success()->title("Test sent to {$user->email}")->body("Personalised as {$sample['name']}.")->send();
            });
    }

    private function saveAsTemplateAction(): Action
    {
        return Action::make('saveAsTemplate')
            ->label('Save as template')
            ->icon('heroicon-o-bookmark')
            ->authorize('create', OutreachEmail::class)
            ->schema([
                TextInput::make('name')
                    ->label('Template name')
                    ->required()
                    ->maxLength(100),
            ])
            ->action(function (array $data): void {
                EmailTemplate::query()->create([
                    'key' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
                    'name' => ['en' => $data['name']],
                    'category' => 'marketing',
                    'subject' => ['en' => $this->record->subject],
                    'body' => ['en' => $this->record->body],
                    'is_active' => true,
                ]);

                Notification::make()->success()->title('Template saved')->body('Choose it under "Start from template" when writing an email.')->send();
            });
    }

    private function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label('Duplicate as new draft')
            ->icon('heroicon-o-document-duplicate')
            ->authorize('replicate', $this->record)
            ->action(function (): void {
                $copy = OutreachEmail::query()->create($this->record->only($this->record->getFillable()));

                $this->redirect(OutreachEmailResource::getUrl('edit', ['record' => $copy]));
            });
    }

    private function currentUser(): User
    {
        /** @var User */
        return auth()->user();
    }
}
