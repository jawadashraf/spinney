<?php

declare(strict_types=1);

use App\Enums\CompanyEngagementType;
use App\Enums\CompanyStatus;
use App\Enums\CompanyVerificationStatus;
use App\Filament\Imports\CompanyImporter;
use App\Filament\Pages\ManagerDashboard;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\CompanyResource\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\EngagementsRelationManager;
use App\Models\Company;
use App\Models\CompanyEngagement;
use App\Models\CompanyType;
use App\Models\Import;
use App\Models\MailingGroup;
use App\Models\People;
use App\Models\Professional;
use App\Models\Role;
use App\Models\Team;
use App\Support\CompanyPermissions;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

describe('access', function () {
    it('lets managers and admins open the directory', function (string $role) {
        signInToDirectoryAs($this->team, $role);

        $this->get(CompanyResource::getUrl('index'))
            ->assertOk();
    })->with(['admin', 'manager']);

    it('refuses a role without directory access', function () {
        signInToDirectoryAs($this->team, 'liaison');

        $this->get(CompanyResource::getUrl('index'))
            ->assertForbidden();
    });

    it('grants directory permissions to managers and admins only', function (string $role, bool $canAccess) {
        $user = signInToDirectoryAs($this->team, $role);

        expect($user->can('viewAny', Company::class))->toBe($canAccess)
            ->and($user->can('create', Company::class))->toBe($canAccess);
    })->with([
        'admin' => ['admin', true],
        'manager' => ['manager', true],
        'liaison' => ['liaison', false],
        'volunteer liaison' => ['volunteer_liaison', false],
        'counselor' => ['counselor', false],
        'assessor' => ['assessor', false],
    ]);

    it('sends staff without directory access to a page they can open after login', function () {
        $user = signInToDirectoryAs($this->team, 'counselor');
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:Schedule', 'guard_name' => 'web']));

        expect(Filament::getHomeUrl())->not->toBe(CompanyResource::getUrl('index'));

        $this->followingRedirects()
            ->get(Filament::getHomeUrl())
            ->assertOk();
    });

    it('sends managers to the manager dashboard', function () {
        signInToDirectoryAs($this->team, 'manager');

        expect(Filament::getHomeUrl())->toBe(ManagerDashboard::getUrl());
    });

    it('revokes company permissions a non-directory role already had', function () {
        $role = Role::firstOrCreate(['name' => 'liaison', 'team_id' => $this->team->id], ['guard_name' => 'web']);
        $role->givePermissionTo('ViewAny:Company');

        CompanyPermissions::ensure($this->team->id);

        expect($role->fresh()->hasPermissionTo('ViewAny:Company'))->toBeFalse();
    });
});

describe('companies', function () {
    beforeEach(function () {
        signInToDirectoryAs($this->team, 'manager');
    });

    it('creates a company with a type, status and mailing groups in the current team', function () {
        Filament::bootCurrentPanel();
        $type = CompanyType::factory()->for($this->team)->create(['name' => 'Masjid']);
        $group = MailingGroup::factory()->for($this->team)->create(['name' => 'Newsletter']);

        livewire(ListCompanies::class)
            ->callAction(CreateAction::class, [
                'name' => 'Central Masjid',
                'company_type_id' => $type->id,
                'status' => CompanyStatus::Active->value,
                'mailingGroups' => [$group->id],
                'email' => 'info@central.test',
                'postcode' => 'LE1 1AA',
            ])
            ->assertHasNoActionErrors();

        $company = Company::query()->where('name', 'Central Masjid')->sole();

        expect($company)
            ->team_id->toBe($this->team->id)
            ->company_type_id->toBe($type->id)
            ->status->toBe(CompanyStatus::Active)
            ->email->toBe('info@central.test')
            ->and($company->mailingGroups->modelKeys())->toBe([$group->id]);
    });

    it('rejects an invalid general email', function () {
        livewire(ListCompanies::class)
            ->callAction(CreateAction::class, ['name' => 'Central Masjid', 'email' => 'not-an-email'])
            ->assertHasActionErrors(['email' => 'email']);
    });

    it('records when the general email was marked invalid and clears it when unmarked', function () {
        $company = Company::factory()->for($this->team)->create();

        livewire(ListCompanies::class)
            ->callAction(TestAction::make('edit')->table($company), ['email_invalid_at' => true])
            ->assertHasNoActionErrors();

        expect($company->refresh()->email_invalid_at)->not->toBeNull();

        livewire(ListCompanies::class)
            ->callAction(TestAction::make('edit')->table($company), ['email_invalid_at' => false])
            ->assertHasNoActionErrors();

        expect($company->refresh()->email_invalid_at)->toBeNull();
    });

    it('filters companies by type and mailing group', function () {
        $masjid = CompanyType::factory()->for($this->team)->create(['name' => 'Masjid']);
        $newsletter = MailingGroup::factory()->for($this->team)->create(['name' => 'Newsletter']);
        $masjidCompany = Company::factory()->for($this->team)->create(['company_type_id' => $masjid->id]);
        $newsletterCompany = Company::factory()->for($this->team)->hasAttached($newsletter)->create();
        $otherCompany = Company::factory()->for($this->team)->create();

        livewire(ListCompanies::class)
            ->filterTable('company_type_id', [$masjid->id])
            ->assertCanSeeTableRecords([$masjidCompany])
            ->assertCanNotSeeTableRecords([$newsletterCompany, $otherCompany]);

        livewire(ListCompanies::class)
            ->filterTable('mailingGroups', [$newsletter->id])
            ->assertCanSeeTableRecords([$newsletterCompany])
            ->assertCanNotSeeTableRecords([$masjidCompany, $otherCompany]);
    });

    it('records when a company is verified and clears it when it is not', function () {
        $company = Company::factory()->for($this->team)->create();

        livewire(ListCompanies::class)
            ->callAction(TestAction::make('edit')->table($company), ['verification_status' => CompanyVerificationStatus::Verified->value])
            ->assertHasNoActionErrors();

        expect($company->refresh()->verified_at)->not->toBeNull();

        livewire(ListCompanies::class)
            ->callAction(TestAction::make('edit')->table($company), ['verification_status' => CompanyVerificationStatus::NeedsChecking->value])
            ->assertHasNoActionErrors();

        expect($company->refresh()->verified_at)->toBeNull();
    });

    it('finds companies with no email for them or any contact', function () {
        $withGeneralEmail = Company::factory()->for($this->team)->create(['email' => 'office@central.test']);
        $withContactEmail = Company::factory()->for($this->team)->create();
        People::factory()->professional()->create(['team_id' => $this->team->id, 'company_id' => $withContactEmail->id]);
        $withoutEmail = Company::factory()->for($this->team)->create();

        livewire(ListCompanies::class)
            ->filterTable('no_email')
            ->assertCanSeeTableRecords([$withoutEmail])
            ->assertCanNotSeeTableRecords([$withGeneralEmail, $withContactEmail]);
    });

    it('only offers the current team\'s company types', function () {
        $ownType = CompanyType::factory()->for($this->team)->create(['name' => 'Masjid']);
        $otherTeamType = CompanyType::factory()->create(['name' => 'Other team type']);

        livewire(ListCompanies::class)
            ->mountAction(CreateAction::class)
            ->assertFormFieldExists('company_type_id', function ($field) use ($ownType, $otherTeamType): bool {
                $options = $field->getOptions();

                return array_key_exists($ownType->id, $options) && ! array_key_exists($otherTeamType->id, $options);
            });
    });
});

describe('contacts and engagement', function () {
    beforeEach(function () {
        $this->manager = signInToDirectoryAs($this->team, 'manager');
        $this->company = Company::factory()->for($this->team)->create();
    });

    it('adds a named contact as a professional in the company\'s team', function () {
        livewire(ContactsRelationManager::class, ['ownerRecord' => $this->company, 'pageClass' => ViewCompany::class])
            ->callAction(TestAction::make('create')->table(), [
                'first_name' => 'Yusuf',
                'last_name' => 'Patel',
                'job_title' => 'Imam',
                'email' => 'imam@central.test',
                'consent_communications' => true,
            ])
            ->assertHasNoActionErrors();

        $contact = People::query()->where('email', 'imam@central.test')->sole();

        expect($contact)
            ->toBeInstanceOf(Professional::class)
            ->team_id->toBe($this->team->id)
            ->company_id->toBe($this->company->id)
            ->job_title->toBe('Imam')
            ->consent_communications->toBeTrue();
    });

    it('logs an engagement against the company and the staff member', function () {
        livewire(EngagementsRelationManager::class, ['ownerRecord' => $this->company, 'pageClass' => ViewCompany::class])
            ->callAction(TestAction::make('create')->table(), [
                'type' => CompanyEngagementType::Talk->value,
                'occurred_on' => '2026-10-01',
                'summary' => 'Recovery awareness talk after Jumuah.',
            ])
            ->assertHasNoActionErrors();

        $engagement = CompanyEngagement::query()->sole();

        expect($engagement)
            ->team_id->toBe($this->team->id)
            ->company_id->toBe($this->company->id)
            ->user_id->toBe($this->manager->id)
            ->type->toBe(CompanyEngagementType::Talk)
            ->and($engagement->occurred_on->toDateString())->toBe('2026-10-01');
    });
});

describe('spreadsheet import', function () {
    beforeEach(function () {
        $user = signInToDirectoryAs($this->team, 'manager');

        $import = Import::query()->create([
            'file_name' => 'directory.csv',
            'file_path' => 'directory.csv',
            'importer' => CompanyImporter::class,
            'total_rows' => 2,
            'user_id' => $user->id,
        ]);

        $columns = collect(CompanyImporter::getColumns())->map(fn ($column): string => $column->getName());
        $this->importer = new CompanyImporter($import, $columns->combine($columns)->all(), []);
    });

    it('creates the company, its type, mailing groups and contact', function () {
        ($this->importer)([
            'name' => 'Central Masjid',
            'type' => 'Masjid',
            'status' => 'active',
            'email' => 'info@central.test',
            'mailing_groups' => 'Newsletter; Ramadan appeal',
            'contact_first_name' => 'Yusuf',
            'contact_last_name' => 'Patel',
            'contact_role' => 'Imam',
            'contact_email' => 'imam@central.test',
        ]);

        $company = Company::query()->where('name', 'Central Masjid')->sole();

        expect($company)
            ->team_id->toBe($this->team->id)
            ->and($company->companyType->name)->toBe('Masjid')
            ->and($company->mailingGroups->pluck('name')->sort()->values()->all())->toBe(['Newsletter', 'Ramadan appeal'])
            ->and($company->contacts->sole())
            ->first_name->toBe('Yusuf')
            ->job_title->toBe('Imam')
            ->consent_communications->toBeTrue();
    });

    it('updates an existing company and adds further contacts on repeated rows', function () {
        $company = Company::factory()->for($this->team)->create(['name' => 'Central Masjid']);

        ($this->importer)(['name' => 'Central Masjid', 'phone' => '0116 000 0000', 'contact_first_name' => 'Yusuf', 'contact_email' => 'imam@central.test']);
        ($this->importer)(['name' => 'Central Masjid', 'contact_first_name' => 'Amina', 'contact_email' => 'office@central.test']);
        ($this->importer)(['name' => 'Central Masjid', 'contact_first_name' => 'Yusuf', 'contact_email' => 'imam@central.test']);

        expect(Company::query()->where('name', 'Central Masjid')->count())->toBe(1)
            ->and($company->refresh()->phone)->toBe('0116 000 0000')
            ->and($company->contacts()->pluck('email')->sort()->values()->all())->toBe(['imam@central.test', 'office@central.test']);
    });

    it('reuses a company type that already exists in the team', function () {
        $masjid = CompanyType::factory()->for($this->team)->create(['name' => 'Masjid']);

        ($this->importer)(['name' => 'Central Masjid', 'type' => 'Masjid']);

        expect(CompanyType::query()->where('team_id', $this->team->id)->where('name', 'Masjid')->count())->toBe(1);
        assertDatabaseHas(Company::class, ['name' => 'Central Masjid', 'company_type_id' => $masjid->id]);
    });

    it('links a site to the organisation it is part of', function () {
        $masjid = Company::factory()->for($this->team)->create(['name' => 'Masjid Ibraheem']);

        ($this->importer)(['name' => 'Madrasah Baytul Ilm', 'part_of' => 'Masjid Ibraheem']);

        expect(Company::query()->where('name', 'Madrasah Baytul Ilm')->sole()->parent_company_id)->toBe($masjid->id);
    });

    it('notes a parent that is not in the directory instead of failing', function () {
        ($this->importer)(['name' => 'Madrasah Baytul Ilm', 'verification_note' => 'Directory record', 'part_of' => 'Unknown Masjid']);

        $company = Company::query()->where('name', 'Madrasah Baytul Ilm')->sole();

        expect($company->parent_company_id)->toBeNull()
            ->and($company->verification_note)->toBe("Directory record\nPart of: Unknown Masjid (not in the directory when imported).");
    });

    it('reads verification statuses written as labels', function () {
        ($this->importer)(['name' => 'Tawakul', 'verification_status' => 'Needs checking']);

        expect(Company::query()->where('name', 'Tawakul')->sole()->verification_status)->toBe(CompanyVerificationStatus::NeedsChecking);
    });
});
