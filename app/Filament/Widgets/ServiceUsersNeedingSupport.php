<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\SupportStatus;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\ServiceUser;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

final class ServiceUsersNeedingSupport extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Service Users Needing Support';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ServiceUser::query()->whereHas('profile', fn ($q) => $q->whereIn('support_status', [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention]))
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Service User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('profile.support_status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('latest_support_note')
                    ->label('Recent Flagged Note')
                    ->state(function (ServiceUser $record) {
                        $note = $record->notes()
                            ->whereIn('support_status', [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention])
                            ->latest()
                            ->first();

                        return $note ? strip_tags($note->body) : '-';
                    })
                    ->limit(60)
                    ->tooltip(function (ServiceUser $record) {
                        $note = $record->notes()
                            ->whereIn('support_status', [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention])
                            ->latest()
                            ->first();

                        return $note ? strip_tags($note->body) : null;
                    }),
                Tables\Columns\TextColumn::make('latest_support_date')
                    ->label('Flagged At')
                    ->state(function (ServiceUser $record) {
                        $note = $record->notes()
                            ->whereIn('support_status', [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention])
                            ->latest()
                            ->first();

                        return $note ? $note->created_at : null;
                    })
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->label('View User')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ServiceUser $record): string => ServiceUserResource::getUrl('edit', ['record' => $record])),
                Action::make('resolve')
                    ->label('Mark as Resolved')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Resolve Support Flag')
                    ->modalDescription('Are you sure you want to mark this service user as resolved? Their status will return to Normal.')
                    ->action(function (ServiceUser $record): void {
                        $record->profile()->update(['support_status' => SupportStatus::Normal]);

                        // We also need to reset the active notes to normal to prevent them from showing up again if re-evaluated.
                        $record->notes()
                            ->whereIn('support_status', [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention])
                            ->update(['support_status' => SupportStatus::Normal]);
                    }),
            ]);
    }
}
