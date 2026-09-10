<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use App\Models\User;
use Filament\Widgets\ChartWidget;

final class OverdueCallsByLiaisonChart extends ChartWidget
{
    protected static ?int $sort = 7;

    protected ?string $heading = 'Overdue calls by liaison';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $overdueByLiaison = CallResource::getEloquentQuery()
            ->overdue()
            ->with('assignee')
            ->get()
            ->groupBy(fn (Call $call): string => $call->assignee->name ?? 'Unassigned')
            ->map(fn ($calls): int => $calls->count())
            ->sortDesc();

        return [
            'datasets' => [
                [
                    'label' => 'Overdue calls',
                    'data' => $overdueByLiaison->values()->all(),
                    'backgroundColor' => '#dc2626',
                ],
            ],
            'labels' => $overdueByLiaison->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && Call::userSeesAllCalls($user);
    }
}
