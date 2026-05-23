<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnquiryDirection;
use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class UpcomingCallsWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Upcoming Calls & Enquiries';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Enquiry::where('direction', EnquiryDirection::OUTBOUND)
                    ->whereIn('status', [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS])
                    ->whereNotNull('due_date')
                    ->orderBy('due_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('people.name')
                    ->label('Contact')
                    ->default('Anonymous')
                    ->searchable(),

                TextColumn::make('call_type')
                    ->badge()
                    ->label('Type'),

                TextColumn::make('due_date')
                    ->dateTime()
                    ->label('Due')
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Dept')
                    ->placeholder('Unassigned')
                    ->badge(),

                TextColumn::make('status')
                    ->badge(),
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
