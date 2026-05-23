<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class RecentEnquiriesWidget extends TableWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Recent Enquiries';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Enquiry::with(['people', 'department'])
                    ->latest()
                    ->limit(15)
            )
            ->columns([
                TextColumn::make('direction')
                    ->badge(),

                TextColumn::make('call_type')
                    ->badge()
                    ->label('Type'),

                TextColumn::make('people.name')
                    ->label('Contact')
                    ->default('Anonymous')
                    ->searchable(),

                TextColumn::make('category')
                    ->badge(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('occurred_at')
                    ->dateTime()
                    ->label('When')
                    ->sortable(),

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
