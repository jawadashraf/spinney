<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\EngagementStatus;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use App\Enums\ServiceTeam;
use App\Filament\Widgets\Reports\EnquiryVolumeReportWidget;
use App\Filament\Widgets\Reports\ServiceUserEngagementReportWidget;
use App\Support\ReportPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Leadership & operational reporting dashboard (AC RP-001, RP-002, RP-005).
 * Provides aggregate enquiry data and service user engagement metrics,
 * filterable by date range, category, status, and service team.
 */
final class ReportsDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Reports & Analytics';

    protected static ?string $title = 'Reports & Analytics';

    protected static string $routePath = 'reports';

    public static function canAccess(): bool
    {
        return ReportPermissions::canViewReports(auth()->user());
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Report Filters')
                ->description('Filter enquiry and service user metrics by date range, category, status, and team.')
                ->icon('heroicon-o-funnel')
                ->collapsible()
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                ])
                ->schema([
                    DatePicker::make('startDate')
                        ->label('From Date'),
                    DatePicker::make('endDate')
                        ->label('To Date'),
                    Select::make('category')
                        ->label('Enquiry Category')
                        ->options(EnquiryCategory::class)
                        ->placeholder('All categories'),
                    Select::make('status')
                        ->label('Enquiry Status')
                        ->options(EnquiryStatus::class)
                        ->placeholder('All statuses'),
                    Select::make('target_service_team')
                        ->label('Service Team')
                        ->options(ServiceTeam::class)
                        ->placeholder('All service teams'),
                    Select::make('engagement_status')
                        ->label('Engagement Status')
                        ->options(EngagementStatus::class)
                        ->placeholder('All statuses'),
                ]),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            EnquiryVolumeReportWidget::class,
            ServiceUserEngagementReportWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 1;
    }
}
