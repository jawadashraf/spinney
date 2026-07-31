<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\SupportStatus;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\ServiceUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
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
                Tables\Columns\TextColumn::make('profile.support_flagged_at')
                    ->label('Flagged At')
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
                    ->form([
                        Textarea::make('resolution_note')
                            ->label('Resolution Details')
                            ->required(false)
                            ->maxLength(500),
                    ])
                    ->modalHeading('Resolve Support Flag')
                    ->modalDescription('Are you sure you want to mark this service user as resolved? You can optionally leave a resolution note for the audit trail.')
                    ->action(function (ServiceUser $record, array $data): void {
                        $record->profile()->update([
                            'support_status' => SupportStatus::Resolved,
                            'support_resolved_at' => now(),
                        ]);

                        $record->notes()
                            ->whereIn('support_status', [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention])
                            ->update(['support_status' => SupportStatus::Resolved]);

                        if (! empty($data['resolution_note'])) {
                            $record->notes()->create([
                                'title' => 'Support Flag Resolved',
                                'body' => strip_tags($data['resolution_note']),
                                'support_status' => SupportStatus::Resolved,
                            ]);
                        }
                    }),
            ]);
    }
}
