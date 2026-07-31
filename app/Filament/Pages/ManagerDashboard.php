<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\ServiceUsersNeedingSupport;
use Filament\Pages\Dashboard;

final class ManagerDashboard extends Dashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Manager Dashboard';

    protected static ?string $title = 'Manager Dashboard';

    protected static string $routePath = 'manager-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manager') ?? false;
    }

    public function getWidgets(): array
    {
        return [
            ServiceUsersNeedingSupport::class,
        ];
    }
}
