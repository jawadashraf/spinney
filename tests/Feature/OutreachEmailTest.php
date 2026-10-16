<?php

declare(strict_types=1);

use App\Actions\Outreach\ResolveOutreachRecipients;
use App\Actions\Outreach\SendOutreachEmail;
use App\Enums\CompanyStatus;
use App\Enums\OutreachEmailStatus;
use App\Enums\OutreachRecipientStatus;
use App\Filament\Resources\OutreachEmails\OutreachEmailResource;
use App\Filament\Resources\OutreachEmails\Pages\CreateOutreachEmail;
use App\Filament\Resources\OutreachEmails\Pages\ViewOutreachEmail;
use App\Models\Company;
use App\Models\CompanyType;
use App\Models\MailingGroup;
use App\Models\OutgoingMail;
use App\Models\OutreachEmail;
use App\Models\OutreachEmailRecipient;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use App\Support\MailQuota;
use Filament\Facades\Filament;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    config(['outreach.daily_limit' => 100, 'outreach.reserved_for_notifications' => 30]);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function outreachCompany(Team $team, array $attributes = []): Company
{
    return Company::factory()->for($team)->create(['email' => fake()->unique()->safeEmail(), ...$attributes]);
}

function outreachContact(Company $company, array $attributes = []): People
{
    return People::factory()->professional()->create([
        'team_id' => $company->team_id,
        'company_id' => $company->id,
        ...$attributes,
    ]);
}

function recordSends(int $recipients, bool $outreach): void
{
    OutgoingMail::query()->create(['recipients' => $recipients, 'is_outreach' => $outreach, 'sent_at' => now()]);
}

describe('choosing recipients', function () {
    it('includes companies in a selected group, of a selected type, or picked by name', function () {
        $group = MailingGroup::factory()->for($this->team)->create();
        $type = CompanyType::factory()->for($this->team)->create();
        $inGroup = outreachCompany($this->team);
        $inGroup->mailingGroups()->attach($group);
        $ofType = outreachCompany($this->team, ['company_type_id' => $type->id]);
        $picked = outreachCompany($this->team);
        $notSelected = outreachCompany($this->team);

        $email = OutreachEmail::factory()->for($this->team)->create([
            'mailing_group_ids' => [$group->id],
            'company_type_ids' => [$type->id],
            'company_ids' => [$picked->id],
            'send_to_contacts' => false,
        ]);

        expect(app(ResolveOutreachRecipients::class)->handle($email)->pluck('email')->sort()->values()->all())
            ->toBe(collect([$inGroup->email, $ofType->email, $picked->email])->sort()->values()->all())
            ->not->toContain($notSelected->email);
    });

    it('never emails inactive companies or another team\'s companies', function () {
        $inactive = outreachCompany($this->team, ['status' => CompanyStatus::Inactive]);
        $otherTeam = outreachCompany(Team::factory()->create());

        $email = OutreachEmail::factory()->for($this->team)->create(['company_ids' => [$inactive->id, $otherTeam->id]]);

        expect(app(ResolveOutreachRecipients::class)->handle($email))->toBeEmpty();
    });

    it('skips invalid, unsubscribed and off-list addresses and emails each address once', function () {
        $company = outreachCompany($this->team, ['email' => 'office@central.test']);
        $onList = outreachContact($company, ['email' => 'imam@central.test']);
        outreachContact($company, ['email' => 'off-list@central.test', 'consent_communications' => false]);
        outreachContact($company, ['email' => 'bounced@central.test', 'email_invalid_at' => now()]);
        outreachContact($company, ['email' => 'left@central.test', 'unsubscribed_at' => now()]);
        outreachContact($company, ['email' => 'OFFICE@central.test']);
        $invalidCompany = outreachCompany($this->team, ['email_invalid_at' => now()]);
        $unsubscribedCompany = outreachCompany($this->team, ['unsubscribed_at' => now()]);

        $email = OutreachEmail::factory()->for($this->team)->create([
            'company_ids' => [$company->id, $invalidCompany->id, $unsubscribedCompany->id],
        ]);

        $recipients = app(ResolveOutreachRecipients::class)->handle($email);

        expect($recipients->pluck('email')->all())->toBe(['office@central.test', 'imam@central.test'])
            ->and($recipients->last()['contact']->is($onList))->toBeTrue();
    });

    it('sends only to the channels chosen', function (bool $general, bool $contacts, array $expected) {
        $company = outreachCompany($this->team, ['email' => 'office@central.test']);
        outreachContact($company, ['email' => 'imam@central.test']);

        $email = OutreachEmail::factory()->for($this->team)->create([
            'company_ids' => [$company->id],
            'send_to_general_emails' => $general,
            'send_to_contacts' => $contacts,
        ]);

        expect(app(ResolveOutreachRecipients::class)->handle($email)->pluck('email')->all())->toBe($expected);
    })->with([
        'general emails only' => [true, false, ['office@central.test']],
        'contacts only' => [false, true, ['imam@central.test']],
    ]);
});

describe('daily quota', function () {
    it('keeps the reserve for notifications', function (int $notifications, int $outreach, int $available) {
        recordSends($notifications, outreach: false);
        recordSends($outreach, outreach: true);

        expect(app(MailQuota::class)->availableForOutreach())->toBe($available);
    })->with([
        'notifications within their reserve' => [20, 0, 70],
        'notifications beyond their reserve' => [45, 0, 55],
        'outreach already sent' => [10, 50, 20],
        'limit used up' => [40, 70, 0],
    ]);

    it('ignores sends older than 24 hours', function () {
        OutgoingMail::query()->create(['recipients' => 60, 'is_outreach' => true, 'sent_at' => now()->subDay()->subMinute()]);

        expect(app(MailQuota::class)->availableForOutreach())->toBe(70);
    });

    it('counts every email the application sends, telling outreach apart', function () {
        Mail::raw('Reminder', fn ($message) => $message->to('staff@spinney.test')->cc('manager@spinney.test'));
        Mail::raw('Outreach', fn ($message) => $message->to('imam@central.test')->getHeaders()->addTextHeader(MailQuota::OUTREACH_HEADER, '1'));

        expect(OutgoingMail::query()->where('is_outreach', false)->sum('recipients'))->toBe(2)
            ->and(OutgoingMail::query()->where('is_outreach', true)->sum('recipients'))->toBe(1);
    });
});

describe('sending', function () {
    beforeEach(function () {
        $this->manager = signInToDirectoryAs($this->team, 'manager');
    });

    it('records each recipient, sends one email per address and logs it against the company', function () {
        Mail::fake();
        $company = outreachCompany($this->team, ['email' => 'office@central.test']);
        outreachContact($company, ['email' => 'imam@central.test']);
        $email = OutreachEmail::factory()->for($this->team)->create(['company_ids' => [$company->id]]);

        $count = app(SendOutreachEmail::class)->handle($email, $this->manager);

        expect($count)->toBe(2)
            ->and($email->refresh()->status)->toBe(OutreachEmailStatus::Sent)
            ->and($email->sent_by_id)->toBe($this->manager->id)
            ->and($email->recipients()->pluck('status')->unique()->all())->toBe([OutreachRecipientStatus::Sent]);

        Mail::assertSent(TemplateMail::class, 2);
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail): bool => $mail->hasTo('imam@central.test'));
        expect(SentEmail::query()->where('sendable_type', $company->getMorphClass())->where('sendable_id', $company->id)->count())->toBe(2);
    });

    it('refuses to send more than the daily allowance and sends nothing', function () {
        Mail::fake();
        recordSends(65, outreach: true);
        $companies = collect(range(1, 6))->map(fn () => outreachCompany($this->team));
        $email = OutreachEmail::factory()->for($this->team)->create(['company_ids' => $companies->pluck('id')->all(), 'send_to_contacts' => false]);

        expect(fn () => app(SendOutreachEmail::class)->handle($email, $this->manager))
            ->toThrow(ValidationException::class, 'only 5 are available');

        expect($email->refresh()->status)->toBe(OutreachEmailStatus::Draft)
            ->and($email->recipients()->count())->toBe(0);
        Mail::assertNothingSent();
    });

    it('refuses an email that has already been sent', function () {
        $email = OutreachEmail::factory()->for($this->team)->create(['company_ids' => [outreachCompany($this->team)->id]]);
        $email->forceFill(['status' => OutreachEmailStatus::Sent])->save();

        expect(fn () => app(SendOutreachEmail::class)->handle($email, $this->manager))
            ->toThrow(ValidationException::class, 'already been sent');
    });

    it('refuses an email with no recipients', function () {
        $email = OutreachEmail::factory()->for($this->team)->create(['company_ids' => []]);

        expect(fn () => app(SendOutreachEmail::class)->handle($email, $this->manager))
            ->toThrow(ValidationException::class, 'No one to send to');
    });

    it('personalises each email, escapes names and adds an unsubscribe link and header', function () {
        $company = outreachCompany($this->team, ['name' => 'Al-Noor <Masjid>']);
        outreachContact($company, ['first_name' => 'Yusuf', 'email' => 'imam@central.test']);
        $email = OutreachEmail::factory()->for($this->team)->create([
            'company_ids' => [$company->id],
            'send_to_general_emails' => false,
            'subject' => 'Hello {{ contact.first_name }}',
            'body' => '<p>Dear {{ contact.first_name }} at {{ company.name }},</p>',
        ]);

        app(SendOutreachEmail::class)->handle($email, $this->manager);

        $message = app('mailer')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $html = $message->getHtmlBody();

        expect($message->getSubject())->toBe('Hello Yusuf')
            ->and($html)->toContain('Dear Yusuf at Al-Noor &lt;Masjid&gt;')
            ->and($html)->not->toContain('<Masjid>')
            ->and($html)->toContain('/outreach/unsubscribe/')
            ->and($message->getHeaders()->get('List-Unsubscribe')?->getBodyAsString())->toContain('/outreach/unsubscribe/')
            ->and($message->getHeaders()->has(MailQuota::OUTREACH_HEADER))->toBeTrue();
    });
});

describe('unsubscribing', function () {
    function sentRecipient(Team $team, ?People $contact, Company $company): OutreachEmailRecipient
    {
        $email = OutreachEmail::factory()->for($team)->create();

        return $email->recipients()->create([
            'team_id' => $team->id,
            'company_id' => $company->id,
            'people_id' => $contact?->id,
            'email' => $contact->email ?? $company->email,
            'status' => OutreachRecipientStatus::Sent,
        ]);
    }

    it('asks for confirmation before unsubscribing when the link is opened', function () {
        $company = outreachCompany($this->team);
        $contact = outreachContact($company);
        $recipient = sentRecipient($this->team, $contact, $company);

        $this->get(URL::signedRoute('outreach.unsubscribe', ['recipient' => $recipient]))
            ->assertOk()
            ->assertSee('Unsubscribe from Spinney Hill emails?');

        expect($contact->refresh()->unsubscribed_at)->toBeNull();
    });

    it('unsubscribes a contact on confirmation or one-click, without a CSRF token', function () {
        $company = outreachCompany($this->team);
        $contact = outreachContact($company);
        $recipient = sentRecipient($this->team, $contact, $company);

        $this->post(URL::signedRoute('outreach.unsubscribe', ['recipient' => $recipient]), ['List-Unsubscribe' => 'One-Click'])
            ->assertOk()
            ->assertSee('You have been unsubscribed');

        expect($contact->refresh()->unsubscribed_at)->not->toBeNull()
            ->and($company->refresh()->unsubscribed_at)->toBeNull();
    });

    it('unsubscribes the company when the email went to its general address', function () {
        $company = outreachCompany($this->team);
        $recipient = sentRecipient($this->team, null, $company);

        $this->post(URL::signedRoute('outreach.unsubscribe', ['recipient' => $recipient]))->assertOk();

        expect($company->refresh()->unsubscribed_at)->not->toBeNull();
    });

    it('rejects a link that has been tampered with', function () {
        $company = outreachCompany($this->team);
        $recipient = sentRecipient($this->team, null, $company);
        $other = sentRecipient($this->team, null, outreachCompany($this->team));

        $url = str_replace("/unsubscribe/{$recipient->id}", "/unsubscribe/{$other->id}", URL::signedRoute('outreach.unsubscribe', ['recipient' => $recipient]));

        $this->post($url)->assertForbidden();

        expect($other->company->refresh()->unsubscribed_at)->toBeNull();
    });
});

describe('screens', function () {
    it('refuses staff without directory access', function () {
        signInToDirectoryAs($this->team, 'liaison');

        $this->get(OutreachEmailResource::getUrl('index'))->assertForbidden();
    });

    it('only lets drafts be edited or sent', function () {
        $manager = signInToDirectoryAs($this->team, 'manager');
        $draft = OutreachEmail::factory()->for($this->team)->create();
        $sent = OutreachEmail::factory()->for($this->team)->create();
        $sent->forceFill(['status' => OutreachEmailStatus::Sent])->save();

        expect($manager->can('send', $draft))->toBeTrue()
            ->and($manager->can('update', $draft))->toBeTrue()
            ->and($manager->can('send', $sent))->toBeFalse()
            ->and($manager->can('update', $sent))->toBeFalse();
    });

    it('starts a draft with the companies chosen on the companies list', function () {
        signInToDirectoryAs($this->team, 'manager');
        $companies = collect([outreachCompany($this->team), outreachCompany($this->team)]);

        livewire(CreateOutreachEmail::class, ['companies' => $companies->implode('id', ',')])
            ->assertSchemaStateSet(['company_ids' => $companies->pluck('id')->all()]);
    });

    it('sends from the email page after confirmation', function () {
        Mail::fake();
        signInToDirectoryAs($this->team, 'manager');
        $email = OutreachEmail::factory()->for($this->team)->create(['company_ids' => [outreachCompany($this->team)->id], 'send_to_contacts' => false]);

        livewire(ViewOutreachEmail::class, ['record' => $email->getKey()])
            ->callAction('send')
            ->assertNotified('Sending to 1 addresses');

        expect($email->refresh()->status)->toBe(OutreachEmailStatus::Sent);
    });

    it('gives system admins the email template, settings and sent-email screens', function (string $path) {
        $this->actingAs(User::factory()->create(['is_system_admin' => true]));

        $this->get("/sysadmin/{$path}")->assertOk();
    })->with([
        'templates' => 'email-templates',
        'sent emails' => 'sent-emails',
        'mail settings' => 'mail-settings/manage-general-settings',
    ]);
});
