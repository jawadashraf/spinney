<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

final class LiaisonDashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Liaison Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Liaison';

    protected static ?int $navigationSort = 0;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string $routePath = 'liaison';

    protected static ?string $title = 'Liaison Dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Enquiry') ?? false;
    }
}
