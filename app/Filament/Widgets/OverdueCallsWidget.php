<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnquiryDirection;
use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class OverdueCallsWidget extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Overdue Calls';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Enquiry::where('direction', EnquiryDirection::OUTBOUND)
                    ->whereIn('status', [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->orderBy('due_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('people.name')
                    ->label('Contact')
                    ->default('Anonymous'),

                TextColumn::make('call_type')
                    ->badge()
                    ->label('Type'),

                TextColumn::make('due_date')
                    ->dateTime()
                    ->label('Was Due')
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Dept')
                    ->placeholder('Unassigned'),

                IconColumn::make('safeguarding_flags')
                    ->boolean()
                    ->label('SG')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'gray'),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Enquiry $record): string => EnquiryResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('ViewAny:Enquiry') ?? false;
    }
}
