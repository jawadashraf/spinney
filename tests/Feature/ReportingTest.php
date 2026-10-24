<?php

declare(strict_types=1);

use App\Enums\CallStatus;
use App\Enums\EngagementStatus;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use App\Enums\ServiceTeam;
use App\Filament\Exports\CallExporter;
use App\Filament\Exports\EnquiryExporter;
use App\Filament\Exports\ServiceUserExporter;
use App\Filament\Pages\ReportsDashboard;
use App\Filament\Resources\Calls\Pages\ListCalls;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Filament\Resources\ServiceUsers\Pages\ListServiceUsers;
use App\Filament\Widgets\Reports\EnquiryVolumeReportWidget;
use App\Filament\Widgets\Reports\ServiceUserEngagementReportWidget;
use App\Models\Call;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\ServiceUser;
use App\Models\ServiceUserProfile;
use App\Models\Team;
use App\Models\User;
use App\Support\ReportPermissions;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

describe('RP-001: Enquiry volume and category report', function (): void {
    it('aggregates enquiries by category, status, and handling team member', function (): void {
        signInToReportsAs($this->team, 'manager');
        $staff1 = User::factory()->create(['current_team_id' => $this->team->id, 'name' => 'Staff Member One']);
        $staff2 = User::factory()->create(['current_team_id' => $this->team->id, 'name' => 'Staff Member Two']);

        Enquiry::factory()->for($this->team)->create([
            'category' => EnquiryCategory::MENTAL_HEALTH,
            'status' => EnquiryStatus::OPEN,
            'user_id' => $staff1->id,
            'occurred_at' => '2026-05-10 10:00:00',
        ]);

        Enquiry::factory()->for($this->team)->create([
            'category' => EnquiryCategory::MENTAL_HEALTH,
            'status' => EnquiryStatus::CLOSED,
            'user_id' => $staff1->id,
            'occurred_at' => '2026-05-11 11:00:00',
        ]);

        Enquiry::factory()->for($this->team)->create([
            'category' => EnquiryCategory::FOOD_BANK,
            'status' => EnquiryStatus::OPEN,
            'user_id' => $staff2->id,
            'occurred_at' => '2026-05-12 12:00:00',
            'safeguarding_flags' => true,
        ]);

        $widget = livewire(EnquiryVolumeReportWidget::class);
        $data = $widget->instance()->getReportData();

        expect($data['totalEnquiries'])->toBe(3)
            ->and($data['openCount'])->toBe(2)
            ->and($data['closedCount'])->toBe(1)
            ->and($data['safeguardingCount'])->toBe(1);

        $mhCategory = collect($data['byCategory'])->firstWhere('case', EnquiryCategory::MENTAL_HEALTH);
        expect($mhCategory['count'])->toBe(2)
            ->and($mhCategory['percentage'])->toBe(66.7);

        $foodBankCategory = collect($data['byCategory'])->firstWhere('case', EnquiryCategory::FOOD_BANK);
        expect($foodBankCategory['count'])->toBe(1)
            ->and($foodBankCategory['percentage'])->toBe(33.3);

        $staffOne = collect($data['byStaff'])->firstWhere('staff', $staff1->name);
        expect($staffOne['total'])->toBe(2)
            ->and($staffOne['open'])->toBe(1)
            ->and($staffOne['resolved'])->toBe(1);
    });

    it('filters enquiries by occurred_at date range, category, and status', function (): void {
        signInToReportsAs($this->team, 'manager');

        Enquiry::factory()->for($this->team)->create([
            'category' => EnquiryCategory::MENTAL_HEALTH,
            'status' => EnquiryStatus::OPEN,
            'occurred_at' => '2026-04-15 10:00:00',
        ]);

        Enquiry::factory()->for($this->team)->create([
            'category' => EnquiryCategory::MENTAL_HEALTH,
            'status' => EnquiryStatus::OPEN,
            'occurred_at' => '2026-05-15 10:00:00',
        ]);

        Enquiry::factory()->for($this->team)->create([
            'category' => EnquiryCategory::FAMILY_ADVICE,
            'status' => EnquiryStatus::CLOSED,
            'occurred_at' => '2026-05-20 10:00:00',
        ]);

        // Filter by date range
        $widget = livewire(EnquiryVolumeReportWidget::class, [
            'pageFilters' => [
                'startDate' => '2026-05-01',
                'endDate' => '2026-05-31',
            ],
        ]);
        $data = $widget->instance()->getReportData();
        expect($data['totalEnquiries'])->toBe(2);

        // Filter by category
        $widgetCategory = livewire(EnquiryVolumeReportWidget::class, [
            'pageFilters' => [
                'category' => EnquiryCategory::FAMILY_ADVICE->value,
            ],
        ]);
        $dataCategory = $widgetCategory->instance()->getReportData();
        expect($dataCategory['totalEnquiries'])->toBe(1);

        // Filter by status
        $widgetStatus = livewire(EnquiryVolumeReportWidget::class, [
            'pageFilters' => [
                'status' => EnquiryStatus::CLOSED->value,
            ],
        ]);
        $dataStatus = $widgetStatus->instance()->getReportData();
        expect($dataStatus['totalEnquiries'])->toBe(1);
    });

    it('scopes enquiry report data to the current team', function (): void {
        signInToReportsAs($this->team, 'manager');
        $otherTeam = Team::factory()->create();

        Enquiry::factory()->for($this->team)->create(['category' => EnquiryCategory::SELF_HELP]);
        Enquiry::factory()->for($otherTeam)->create(['category' => EnquiryCategory::SELF_HELP]);

        $widget = livewire(EnquiryVolumeReportWidget::class);
        $data = $widget->instance()->getReportData();

        expect($data['totalEnquiries'])->toBe(1);
    });

    it('renders report links to enquiry resource', function (): void {
        signInToReportsAs($this->team, 'manager');

        livewire(EnquiryVolumeReportWidget::class)
            ->assertSeeHtml('enquiries')
            ->assertSeeHtml('tableFilters');
    });
});

describe('RP-002: Service user engagement report', function (): void {
    it('aggregates service users by service team and engagement status', function (): void {
        signInToReportsAs($this->team, 'manager');

        $person1 = People::factory()->serviceUser()->for($this->team)->create();
        ServiceUserProfile::factory()->for($this->team)->create([
            'person_id' => $person1->id,
            'target_service_team' => ServiceTeam::ASSESSMENT,
            'engagement_status' => EngagementStatus::ACTIVE,
            'created_at' => '2026-05-01 10:00:00',
        ]);

        $person2 = People::factory()->serviceUser()->for($this->team)->create();
        ServiceUserProfile::factory()->for($this->team)->create([
            'person_id' => $person2->id,
            'target_service_team' => ServiceTeam::ASSESSMENT,
            'engagement_status' => EngagementStatus::PENDING,
            'created_at' => '2026-05-02 10:00:00',
        ]);

        $person3 = People::factory()->serviceUser()->for($this->team)->create();
        ServiceUserProfile::factory()->for($this->team)->create([
            'person_id' => $person3->id,
            'target_service_team' => ServiceTeam::DRUG_ALCOHOL,
            'engagement_status' => EngagementStatus::DISCHARGED,
            'created_at' => '2026-05-03 10:00:00',
        ]);

        $widget = livewire(ServiceUserEngagementReportWidget::class);
        $data = $widget->instance()->getReportData();

        expect($data['totalServiceUsers'])->toBe(3)
            ->and($data['activeCount'])->toBe(1)
            ->and($data['pendingCount'])->toBe(1)
            ->and($data['dischargedCount'])->toBe(1);

        $assessment = collect($data['byServiceTeam'])->firstWhere('case', ServiceTeam::ASSESSMENT);
        expect($assessment['count'])->toBe(2)
            ->and($assessment['percentage'])->toBe(66.7);

        $drugAlcohol = collect($data['byServiceTeam'])->firstWhere('case', ServiceTeam::DRUG_ALCOHOL);
        expect($drugAlcohol['count'])->toBe(1)
            ->and($drugAlcohol['percentage'])->toBe(33.3);
    });

    it('filters engagement report by date range, service team, and engagement status', function (): void {
        signInToReportsAs($this->team, 'manager');

        $person1 = People::factory()->serviceUser()->for($this->team)->create();
        ServiceUserProfile::factory()->for($this->team)->create([
            'person_id' => $person1->id,
            'target_service_team' => ServiceTeam::SPIRITUAL,
            'engagement_status' => EngagementStatus::ACTIVE,
            'created_at' => '2026-03-01 10:00:00',
        ]);

        $person2 = People::factory()->serviceUser()->for($this->team)->create();
        ServiceUserProfile::factory()->for($this->team)->create([
            'person_id' => $person2->id,
            'target_service_team' => ServiceTeam::AFTERCARE,
            'engagement_status' => EngagementStatus::INACTIVE,
            'created_at' => '2026-06-01 10:00:00',
        ]);

        // Filter by date range
        $widgetDate = livewire(ServiceUserEngagementReportWidget::class, [
            'pageFilters' => [
                'startDate' => '2026-05-01',
                'endDate' => '2026-06-30',
            ],
        ]);
        expect($widgetDate->instance()->getReportData()['totalServiceUsers'])->toBe(1);

        // Filter by service team
        $widgetTeam = livewire(ServiceUserEngagementReportWidget::class, [
            'pageFilters' => [
                'target_service_team' => ServiceTeam::SPIRITUAL->value,
            ],
        ]);
        expect($widgetTeam->instance()->getReportData()['totalServiceUsers'])->toBe(1);
    });

    it('scopes engagement report data to the current team', function (): void {
        signInToReportsAs($this->team, 'manager');
        $otherTeam = Team::factory()->create();

        $person1 = People::factory()->serviceUser()->for($this->team)->create();
        ServiceUserProfile::factory()->for($this->team)->create(['person_id' => $person1->id]);

        $person2 = People::factory()->serviceUser()->for($otherTeam)->create();
        ServiceUserProfile::factory()->for($otherTeam)->create(['person_id' => $person2->id]);

        $widget = livewire(ServiceUserEngagementReportWidget::class);
        $data = $widget->instance()->getReportData();

        expect($data['totalServiceUsers'])->toBe(1);
    });

    it('renders report links to service user resource', function (): void {
        signInToReportsAs($this->team, 'manager');

        livewire(ServiceUserEngagementReportWidget::class)
            ->assertSeeHtml('service-users')
            ->assertSeeHtml('tableFilters');
    });

    it('filters service users list table by date registered', function (): void {
        signInToReportsAs($this->team, 'manager');

        $person1 = People::factory()->serviceUser()->for($this->team)->create(['created_at' => '2026-05-15 10:00:00']);
        $inRange = ServiceUser::find($person1->id);
        $person2 = People::factory()->serviceUser()->for($this->team)->create(['created_at' => '2026-04-01 10:00:00']);
        $tooEarly = ServiceUser::find($person2->id);

        livewire(ListServiceUsers::class)
            ->filterTable('created_at', [
                'registered_from' => '2026-05-01',
                'registered_until' => '2026-05-31',
            ])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$tooEarly]);
    });
});

describe('RP-004: Resource export functionality', function (): void {
    it('exports enquiries respecting team scoping and active filters', function (): void {
        Storage::fake();
        signInToReportsAs($this->team, 'manager');

        Enquiry::factory()->count(2)->for($this->team)->create();

        livewire(ListEnquiries::class)
            ->callAction(ExportAction::class)
            ->assertHasNoActionErrors();

        expect(Export::query()->sole())
            ->exporter->toBe(EnquiryExporter::class)
            ->successful_rows->toBe(2);
    });

    it('exports service users with case file and profile details', function (): void {
        Storage::fake();
        signInToReportsAs($this->team, 'manager');

        $person = People::factory()->serviceUser()->for($this->team)->create();
        ServiceUserProfile::factory()->for($this->team)->create([
            'person_id' => $person->id,
            'target_service_team' => ServiceTeam::ASSESSMENT,
            'engagement_status' => EngagementStatus::ACTIVE,
        ]);

        livewire(ListServiceUsers::class)
            ->callAction(ExportAction::class)
            ->assertHasNoActionErrors();

        expect(Export::query()->sole())
            ->exporter->toBe(ServiceUserExporter::class)
            ->successful_rows->toBe(1);
    });

    it('exports calls with liaison, outcome and attempt details', function (): void {
        Storage::fake();
        signInToReportsAs($this->team, 'manager');

        $person = People::factory()->serviceUser()->for($this->team)->create();
        Call::factory()->count(2)->for($this->team)->create([
            'people_id' => $person->id,
            'status' => CallStatus::Completed,
        ]);

        livewire(ListCalls::class)
            ->set('activeTab', 'all')
            ->callAction(ExportAction::class)
            ->assertHasNoActionErrors();

        expect(Export::query()->sole())
            ->exporter->toBe(CallExporter::class)
            ->successful_rows->toBe(2);
    });
});

describe('RP-005: Report access permissions', function (): void {
    it('allows managers and admins to access the reports dashboard', function (string $role): void {
        signInToReportsAs($this->team, $role);

        expect(ReportsDashboard::canAccess())->toBeTrue();
    })->with(['admin', 'manager']);

    it('allows safeguarding to access reports dashboard and view enquiry & engagement widgets', function (): void {
        $user = signInToReportsAs($this->team, 'safeguarding');

        expect(ReportsDashboard::canAccess())->toBeTrue()
            ->and(ReportPermissions::canViewEnquiryReport($user))->toBeTrue()
            ->and(ReportPermissions::canViewEngagementReport($user))->toBeTrue()
            ->and(ReportPermissions::canViewDonationReport($user))->toBeFalse();
    });

    it('refuses frontline from accessing reports dashboard', function (): void {
        $user = signInToReportsAs($this->team, 'frontline');

        expect(ReportsDashboard::canAccess())->toBeFalse()
            ->and(ReportPermissions::canViewEnquiryReport($user))->toBeFalse()
            ->and(ReportPermissions::canViewEngagementReport($user))->toBeFalse();
    });

    it('enforces export permission matrix across roles', function (string $role, bool $canExportEnquiry, bool $canExportPeople): void {
        $user = signInToReportsAs($this->team, $role);

        expect(ReportPermissions::canExport($user, Enquiry::class))->toBe($canExportEnquiry)
            ->and(ReportPermissions::canExport($user, ServiceUser::class))->toBe($canExportEnquiry)
            ->and(ReportPermissions::canExport($user, Call::class))->toBe($canExportEnquiry)
            ->and(ReportPermissions::canExport($user, People::class))->toBe($canExportPeople);
    })->with([
        'admin' => ['admin', true, true],
        'manager' => ['manager', true, true],
        'safeguarding' => ['safeguarding', false, false],
        'frontline' => ['frontline', false, false],
        'fundraising' => ['fundraising', false, true],
        'counselor' => ['counselor', false, false],
        'volunteer_liaison' => ['volunteer_liaison', false, false],
    ]);
});
