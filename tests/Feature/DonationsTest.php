<?php

declare(strict_types=1);

use App\Enums\DonationPaymentMethod;
use App\Enums\DonationPurpose;
use App\Enums\GiftAidStatus;
use App\Filament\Exports\DonationExporter;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\Donations\DonationResource;
use App\Filament\Resources\Donations\Pages\ListDonations;
use App\Filament\Resources\Donations\RelationManagers\DonationsRelationManager;
use App\Filament\Resources\Donations\Schemas\DonationForm;
use App\Filament\Resources\PeopleResource;
use App\Filament\Resources\PeopleResource\Pages\ListPeople;
use App\Filament\Resources\PeopleResource\Pages\ViewPeople;
use App\Filament\Resources\PeopleResource\RelationManagers\GiftAidDeclarationsRelationManager;
use App\Models\Company;
use App\Models\Donation;
use App\Models\GiftAidDeclaration;
use App\Models\People;
use App\Models\Role;
use App\Models\Team;
use App\Support\DonationPermissions;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

describe('access', function (): void {
    it('lets managers and admins open the donations list', function (string $role): void {
        signInToDonationsAs($this->team, $role);

        $this->get(DonationResource::getUrl('index'))
            ->assertOk();
    })->with(['admin', 'manager']);

    it('refuses a role without donation access', function (): void {
        signInToDonationsAs($this->team, 'liaison');

        $this->get(DonationResource::getUrl('index'))
            ->assertForbidden();
    });

    it('lets managers record and edit donations and only admins delete them', function (string $role, bool $canView, bool $canEdit, bool $canDelete): void {
        $user = signInToDonationsAs($this->team, $role);
        $donation = Donation::factory()->for(People::factory()->for($this->team), 'donor')->create();

        expect($user->can('viewAny', Donation::class))->toBe($canView)
            ->and($user->can('create', Donation::class))->toBe($canEdit)
            ->and($user->can('update', $donation))->toBe($canEdit)
            ->and($user->can('delete', $donation))->toBe($canDelete)
            ->and($user->can('create', GiftAidDeclaration::class))->toBe($canEdit);
    })->with([
        'admin' => ['admin', true, true, true],
        'manager' => ['manager', true, true, false],
        'liaison' => ['liaison', false, false, false],
        'volunteer liaison' => ['volunteer_liaison', false, false, false],
        'counselor' => ['counselor', false, false, false],
        'assessor' => ['assessor', false, false, false],
    ]);

    it('revokes donation permissions a role should not hold', function (): void {
        $manager = Role::firstOrCreate(['name' => 'manager', 'team_id' => $this->team->id], ['guard_name' => 'web']);
        $liaison = Role::firstOrCreate(['name' => 'liaison', 'team_id' => $this->team->id], ['guard_name' => 'web']);
        DonationPermissions::ensure($this->team->id);
        $manager->givePermissionTo('Delete:Donation');
        $liaison->givePermissionTo('ViewAny:Donation');

        DonationPermissions::ensure($this->team->id);

        expect($manager->fresh()->hasPermissionTo('Delete:Donation'))->toBeFalse()
            ->and($manager->fresh()->hasPermissionTo('Update:Donation'))->toBeTrue()
            ->and($liaison->fresh()->hasPermissionTo('ViewAny:Donation'))->toBeFalse();
    });

    it('hides the donation and Gift Aid tabs on profiles from staff without donation access', function (): void {
        signInToDonationsAs($this->team, 'liaison');
        $person = People::factory()->for($this->team)->create();
        $company = Company::factory()->for($this->team)->create();

        expect(DonationsRelationManager::canViewForRecord($person, ViewPeople::class))->toBeFalse()
            ->and(DonationsRelationManager::canViewForRecord($company, ViewCompany::class))->toBeFalse()
            ->and(GiftAidDeclarationsRelationManager::canViewForRecord($person, ViewPeople::class))->toBeFalse();
    });

    it('shows the donation and Gift Aid tabs on profiles to managers', function (): void {
        signInToDonationsAs($this->team, 'manager');
        $person = People::factory()->for($this->team)->create();

        expect(DonationsRelationManager::canViewForRecord($person, ViewPeople::class))->toBeTrue()
            ->and(GiftAidDeclarationsRelationManager::canViewForRecord($person, ViewPeople::class))->toBeTrue();
    });

    it('hides the donors tab on the people list from staff without donation access', function (): void {
        $user = signInToDonationsAs($this->team, 'liaison');
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:People', 'guard_name' => 'web']));
        Filament::bootCurrentPanel();

        $tabs = livewire(ListPeople::class)->instance()->getTabs();

        expect($tabs['donors']->isVisible())->toBeFalse();
    });

    it('lists everyone who has given a donation under the donors tab, whatever kind of person they are', function (): void {
        $user = signInToDonationsAs($this->team, 'manager');
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:People', 'guard_name' => 'web']));
        $professional = People::factory()->professional()->for($this->team)->create();
        Donation::factory()->for($professional, 'donor')->create();
        $nonDonor = People::factory()->for($this->team)->create();
        Filament::bootCurrentPanel();

        livewire(ListPeople::class, ['activeTab' => 'donors'])
            ->assertCanSeeTableRecords([$professional])
            ->assertCanNotSeeTableRecords([$nonDonor]);
    });

    it('registers People and Donors in navigation for managers', function (): void {
        $user = signInToDonationsAs($this->team, 'manager');
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:People', 'guard_name' => 'web']));
        Filament::bootCurrentPanel();

        expect(PeopleResource::shouldRegisterNavigation())->toBeTrue();

        $groups = Filament::getCurrentPanel()->getNavigation();
        $workspace = collect($groups)->first(fn ($group) => $group->getLabel() === 'Workspace');
        expect($workspace)->not->toBeNull();

        $items = collect($workspace->getItems());
        $donorItem = $items->first(fn ($item) => $item->getLabel() === 'Donors');
        $peopleItem = $items->first(fn ($item) => $item->getLabel() === 'People');

        expect($donorItem)->not->toBeNull()
            ->and($donorItem->getUrl())->toContain('activeTab=donors')
            ->and($peopleItem)->not->toBeNull();
    });

    it('can create a person without an organisation', function (): void {
        $user = signInToDonationsAs($this->team, 'manager');
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:People', 'guard_name' => 'web']));
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'Create:People', 'guard_name' => 'web']));
        Filament::bootCurrentPanel();

        livewire(ListPeople::class)
            ->callAction(CreateAction::class, [
                'first_name' => 'Fatima',
                'last_name' => 'Zahra',
                'email' => 'fatima@example.com',
                'company_id' => null,
            ])
            ->assertHasNoActionErrors();

        $person = People::query()->where('email', 'fatima@example.com')->first();
        expect($person)->not->toBeNull()
            ->and($person->company_id)->toBeNull()
            ->and($person->name)->toBe('Fatima Zahra');
    });
});

describe('recording donations', function (): void {
    beforeEach(function (): void {
        Carbon::setTestNow('2026-10-18 10:00');
    });

    it('records a donation from a person in the current team', function (): void {
        $user = signInToDonationsAs($this->team, 'manager');
        $person = People::factory()->for($this->team)->create();

        livewire(ListDonations::class)
            ->callAction(CreateAction::class, [
                'donor_type' => 'people',
                'donor_id' => $person->id,
                'amount' => '50',
                'donated_at' => '2026-10-17',
                'payment_method' => DonationPaymentMethod::Cash->value,
                'purpose' => DonationPurpose::Zakat->value,
            ])
            ->assertHasNoActionErrors();

        $donation = Donation::query()->sole();

        expect($donation)
            ->team_id->toBe($this->team->id)
            ->donor_type->toBe('people')
            ->donor_id->toBe($person->id)
            ->amount->toBe('50.00')
            ->purpose->toBe(DonationPurpose::Zakat)
            ->gift_aid_status->toBe(GiftAidStatus::NotEligible)
            ->creator_id->toBe($user->id);
    });

    it('records a donation from a company', function (): void {
        signInToDonationsAs($this->team, 'manager');
        $company = Company::factory()->for($this->team)->create();

        livewire(ListDonations::class)
            ->callAction(CreateAction::class, [
                'donor_type' => 'company',
                'donor_id' => $company->id,
                'amount' => '250',
                'donated_at' => '2026-10-01',
                'payment_method' => DonationPaymentMethod::BankTransfer->value,
                'purpose' => DonationPurpose::FoodBank->value,
            ])
            ->assertHasNoActionErrors();

        expect(Donation::query()->sole()->donor->is($company))->toBeTrue();
    });

    it('requires a donor, amount, date, payment method and purpose', function (): void {
        signInToDonationsAs($this->team, 'manager');

        livewire(ListDonations::class)
            ->callAction(CreateAction::class, [
                'donor_type' => null,
                'amount' => null,
                'donated_at' => null,
                'payment_method' => null,
                'purpose' => null,
            ])
            ->assertHasActionErrors([
                'donor_type' => 'required',
                'amount' => 'required',
                'donated_at' => 'required',
                'payment_method' => 'required',
                'purpose' => 'required',
            ]);

        expect(Donation::query()->exists())->toBeFalse();
    });

    it('refuses a donor from another team', function (): void {
        signInToDonationsAs($this->team, 'manager');
        $outsider = People::factory()->create();

        livewire(ListDonations::class)
            ->callAction(CreateAction::class, [
                'donor_type' => 'people',
                'donor_id' => $outsider->id,
                'amount' => '10',
                'donated_at' => '2026-10-17',
                'payment_method' => DonationPaymentMethod::Cash->value,
                'purpose' => DonationPurpose::General->value,
            ])
            ->assertHasActionErrors(['donor_id']);

        expect(Donation::query()->exists())->toBeFalse();
    });

    it('marks a donation as Gift Aid and links the declaration when one covers its date', function (): void {
        signInToDonationsAs($this->team, 'manager');
        $person = People::factory()->for($this->team)->create();
        $declaration = GiftAidDeclaration::factory()->for($person, 'person')->create(['declared_on' => '2026-01-10']);

        livewire(ListDonations::class)
            ->callAction(CreateAction::class, [
                'donor_type' => 'people',
                'donor_id' => $person->id,
                'amount' => '20',
                'donated_at' => '2026-10-17',
                'payment_method' => DonationPaymentMethod::Card->value,
                'purpose' => DonationPurpose::General->value,
            ])
            ->assertHasNoActionErrors();

        expect(Donation::query()->sole())
            ->gift_aid_status->toBe(GiftAidStatus::Eligible)
            ->gift_aid_declaration_id->toBe($declaration->id);
    });

    it('refuses Gift Aid when no declaration covers the date', function (): void {
        signInToDonationsAs($this->team, 'manager');
        $person = People::factory()->for($this->team)->create();
        GiftAidDeclaration::factory()->for($person, 'person')->create([
            'declared_on' => '2026-09-01',
            'covers_past_donations' => false,
        ]);

        livewire(ListDonations::class)
            ->callAction(CreateAction::class, [
                'donor_type' => 'people',
                'donor_id' => $person->id,
                'amount' => '20',
                'donated_at' => '2026-08-01',
                'payment_method' => DonationPaymentMethod::Card->value,
                'purpose' => DonationPurpose::General->value,
                'gift_aid_status' => GiftAidStatus::Eligible->value,
            ])
            ->assertHasActionErrors(['gift_aid_status' => 'No Gift Aid declaration from this donor covers this date.']);

        expect(Donation::query()->exists())->toBeFalse();
    });

    it('never treats a company donation as Gift Aid', function (): void {
        $donation = Donation::factory()
            ->for(Company::factory()->for($this->team), 'donor')
            ->create(['gift_aid_status' => GiftAidStatus::Eligible]);

        expect($donation->gift_aid_status)->toBe(GiftAidStatus::NotEligible);
    });

    it('records a donation from a person\'s profile', function (): void {
        signInToDonationsAs($this->team, 'manager');
        $person = People::factory()->for($this->team)->create();

        livewire(DonationsRelationManager::class, ['ownerRecord' => $person, 'pageClass' => ViewPeople::class])
            ->callAction(TestAction::make('create')->table(), [
                'amount' => '15',
                'donated_at' => '2026-10-10',
                'payment_method' => DonationPaymentMethod::Online->value,
                'purpose' => DonationPurpose::Sadaqah->value,
            ])
            ->assertHasNoActionErrors();

        expect(Donation::query()->sole())
            ->team_id->toBe($this->team->id)
            ->and(Donation::query()->sole()->donor->is($person))->toBeTrue();
    });

    it('allows inline creation of a person donor when recording a donation', function (): void {
        signInToDonationsAs($this->team, 'manager');
        $lw = livewire(ListDonations::class);
        $schema = DonationForm::configure((new Schema($lw->instance()))->model(Donation::class));
        $morphSelect = collect($schema->getComponents())->first(fn ($c) => $c instanceof MorphToSelect);
        $peopleType = $morphSelect->getTypes()['people'];

        $keySelect = new Select('donor_id');
        $callback = $peopleType->getModifyKeySelectUsingCallback();
        $callback($keySelect);

        $createUsing = $keySelect->getCreateOptionUsing();
        expect($createUsing)->not->toBeNull();

        $personId = $createUsing([
            'first_name' => 'Khadijah',
            'last_name' => 'Ali',
            'email' => 'khadijah@example.com',
            'phone' => '07123456789',
            'postcode' => 'LE2 0AA',
            'address' => '10 High Street',
        ]);

        $person = People::query()->find($personId);
        expect($person)->not->toBeNull()
            ->and($person->name)->toBe('Khadijah Ali')
            ->and($person->email)->toBe('khadijah@example.com')
            ->and($person->team_id)->toBe($this->team->id);
    });

    it('allows inline creation of a company donor when recording a donation', function (): void {
        signInToDonationsAs($this->team, 'manager');
        $lw = livewire(ListDonations::class);
        $schema = DonationForm::configure((new Schema($lw->instance()))->model(Donation::class));
        $morphSelect = collect($schema->getComponents())->first(fn ($c) => $c instanceof MorphToSelect);
        $companyType = $morphSelect->getTypes()['company'];

        $keySelect = new Select('donor_id');
        $callback = $companyType->getModifyKeySelectUsingCallback();
        $callback($keySelect);

        $createUsing = $keySelect->getCreateOptionUsing();
        expect($createUsing)->not->toBeNull();

        $companyId = $createUsing([
            'name' => 'Leicester Community Trust',
            'email' => 'contact@lct.org',
            'phone' => '01161234567',
        ]);

        $company = Company::query()->find($companyId);
        expect($company)->not->toBeNull()
            ->and($company->name)->toBe('Leicester Community Trust')
            ->and($company->team_id)->toBe($this->team->id);
    });
});

describe('Gift Aid declarations', function (): void {
    it('covers gifts by their date', function (array $declaration, string $donatedAt, bool $covered): void {
        $declaration = GiftAidDeclaration::factory()->make(['declared_on' => '2026-06-01', ...$declaration]);

        expect($declaration->covers(Carbon::parse($donatedAt)))->toBe($covered);
    })->with([
        'gift on the declaration day' => [['covers_past_donations' => false, 'covers_future_donations' => false], '2026-06-01', true],
        'later gift' => [[], '2026-09-01', true],
        'gift in the four years before' => [[], '2022-06-01', true],
        'gift more than four years before' => [[], '2022-05-31', false],
        'later gift when only past gifts are covered' => [['covers_future_donations' => false], '2026-09-01', false],
        'earlier gift when only future gifts are covered' => [['covers_past_donations' => false], '2026-05-01', false],
        'gift before the withdrawal' => [['withdrawn_on' => '2026-08-01'], '2026-07-31', true],
        'gift on the withdrawal day' => [['withdrawn_on' => '2026-08-01'], '2026-08-01', false],
    ]);

    it('stops unclaimed gifts after a withdrawal being Gift Aid', function (): void {
        Carbon::setTestNow('2026-10-18 10:00');
        signInToDonationsAs($this->team, 'manager');
        $person = People::factory()->for($this->team)->create();
        $declaration = GiftAidDeclaration::factory()->for($person, 'person')->create(['declared_on' => '2026-01-01']);
        $before = Donation::factory()->for($person, 'donor')->create(['donated_at' => '2026-03-01', 'gift_aid_status' => GiftAidStatus::Eligible]);
        $after = Donation::factory()->for($person, 'donor')->create(['donated_at' => '2026-09-01', 'gift_aid_status' => GiftAidStatus::Eligible]);
        $claimed = Donation::factory()->for($person, 'donor')->create(['donated_at' => '2026-09-02', 'gift_aid_status' => GiftAidStatus::Claimed]);

        livewire(GiftAidDeclarationsRelationManager::class, ['ownerRecord' => $person, 'pageClass' => ViewPeople::class])
            ->callAction(TestAction::make('withdraw')->table($declaration), ['withdrawn_on' => '2026-08-01'])
            ->assertHasNoActionErrors();

        expect($declaration->refresh()->withdrawn_on->toDateString())->toBe('2026-08-01')
            ->and($after->refresh())
            ->gift_aid_status->toBe(GiftAidStatus::NotEligible)
            ->gift_aid_declaration_id->toBeNull()
            ->and($before->refresh())
            ->gift_aid_status->toBe(GiftAidStatus::Eligible)
            ->gift_aid_declaration_id->toBe($declaration->id)
            ->and($claimed->refresh()->gift_aid_status)->toBe(GiftAidStatus::Claimed);
    });

    it('keeps a declaration that donations are linked to', function (): void {
        $admin = signInToDonationsAs($this->team, 'admin');
        $person = People::factory()->for($this->team)->create();
        $used = GiftAidDeclaration::factory()->for($person, 'person')->create(['declared_on' => '2026-01-01']);
        $unused = GiftAidDeclaration::factory()->for(People::factory()->for($this->team), 'person')->create();
        Donation::factory()->for($person, 'donor')->create(['donated_at' => '2026-03-01', 'gift_aid_status' => GiftAidStatus::Eligible]);

        expect($admin->can('delete', $used))->toBeFalse()
            ->and($admin->can('delete', $unused))->toBeTrue();
    });
});

describe('reporting', function (): void {
    beforeEach(function (): void {
        signInToDonationsAs($this->team, 'manager');
    });

    it('filters donations by the date they were received', function (): void {
        $person = People::factory()->for($this->team)->create();
        $inRange = Donation::factory()->for($person, 'donor')->create(['donated_at' => '2026-04-15']);
        $tooEarly = Donation::factory()->for($person, 'donor')->create(['donated_at' => '2026-03-31']);
        $tooLate = Donation::factory()->for($person, 'donor')->create(['donated_at' => '2026-05-01']);

        livewire(ListDonations::class)
            ->filterTable('donated_at', ['from' => '2026-04-01', 'until' => '2026-04-30'])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$tooEarly, $tooLate]);
    });

    it('filters donations by donor, telling people and companies apart', function (): void {
        $person = People::factory()->for($this->team)->create();
        $company = Company::factory()->for($this->team)->create(['id' => $person->id]);
        $fromPerson = Donation::factory()->for($person, 'donor')->create();
        $fromCompany = Donation::factory()->for($company, 'donor')->create();

        livewire(ListDonations::class)
            ->filterTable('donor', ['donor' => "people:{$person->id}"])
            ->assertCanSeeTableRecords([$fromPerson])
            ->assertCanNotSeeTableRecords([$fromCompany]);
    });

    it('ignores a donor filter value that is not a donor key', function (): void {
        $donations = Donation::factory()->count(2)->for(People::factory()->for($this->team), 'donor')->create();

        livewire(ListDonations::class)
            ->filterTable('donor', ['donor' => "people:1' OR '1'='1"])
            ->assertCanSeeTableRecords($donations);
    });

    it('filters donations by Gift Aid status', function (): void {
        $person = People::factory()->for($this->team)->create();
        GiftAidDeclaration::factory()->for($person, 'person')->create(['declared_on' => '2025-01-01']);
        $eligible = Donation::factory()->for($person, 'donor')->create(['donated_at' => '2026-02-01', 'gift_aid_status' => GiftAidStatus::Eligible]);
        $notEligible = Donation::factory()->for($person, 'donor')->create(['donated_at' => '2026-02-02']);

        livewire(ListDonations::class)
            ->filterTable('gift_aid_status', [GiftAidStatus::Eligible->value])
            ->assertCanSeeTableRecords([$eligible])
            ->assertCanNotSeeTableRecords([$notEligible]);
    });

    it('totals the donations and the Gift Aid they can claim', function (): void {
        $person = People::factory()->for($this->team)->create();
        GiftAidDeclaration::factory()->for($person, 'person')->create(['declared_on' => '2025-01-01']);
        Donation::factory()->for($person, 'donor')->create(['amount' => 100, 'donated_at' => '2026-02-01', 'gift_aid_status' => GiftAidStatus::Eligible]);
        Donation::factory()->for($person, 'donor')->create(['amount' => 60, 'donated_at' => '2026-02-02', 'gift_aid_status' => GiftAidStatus::Claimed]);
        Donation::factory()->for($person, 'donor')->create(['amount' => 40, 'donated_at' => '2026-02-03', 'gift_aid_status' => GiftAidStatus::NotEligible]);

        livewire(ListDonations::class)
            ->assertTableColumnSummarySet('amount', 'total', 200)
            ->assertTableColumnSummarySet('amount', 'gift_aid_value', 40);
    });

    it('groups donations by donor', function (): void {
        $person = People::factory()->for($this->team)->create(['first_name' => 'Amina', 'last_name' => 'Patel']);
        $company = Company::factory()->for($this->team)->create(['name' => 'Central Masjid']);
        Donation::factory()->for($person, 'donor')->create();
        Donation::factory()->for($company, 'donor')->create();

        livewire(ListDonations::class)
            ->set('tableGrouping', 'donor')
            ->assertSuccessful()
            ->assertSee('Amina Patel')
            ->assertSee('Central Masjid');
    });

    it('does not list donations from another team', function (): void {
        $ours = Donation::factory()->for(People::factory()->for($this->team), 'donor')->create();
        $theirs = Donation::factory()->for(People::factory(), 'donor')->create();
        Filament::bootCurrentPanel();

        livewire(ListDonations::class)
            ->assertCanSeeTableRecords([$ours])
            ->assertCanNotSeeTableRecords([$theirs]);
    });

    it('exports the donations', function (): void {
        Storage::fake();
        Donation::factory()->count(2)->for(People::factory()->for($this->team), 'donor')->create();

        livewire(ListDonations::class)
            ->callAction(ExportAction::class)
            ->assertHasNoActionErrors();

        expect(Export::query()->sole())
            ->exporter->toBe(DonationExporter::class)
            ->successful_rows->toBe(2);
    });
});
