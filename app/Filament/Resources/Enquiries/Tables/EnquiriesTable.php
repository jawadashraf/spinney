<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Tables;

use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\Actions\CloseEnquiryAction;
use App\Filament\Resources\Enquiries\Actions\ConvertToServiceUserAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('category')
                    ->badge()
                    ->sortable(),

                TextColumn::make('people.name')
                    ->label('Caller')
                    ->searchable()
                    ->default('Anonymous'),

                TextColumn::make('reason_for_contact')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn (string $state): string => $state),

                IconColumn::make('safeguarding_flags')
                    ->boolean()
                    ->label('Safeguarding')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'gray'),

                TextColumn::make('user.name')
                    ->label('Staff')
                    ->sortable(),

                TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Logged')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(EnquiryCategory::class),

                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Staff Member'),

                SelectFilter::make('status')
                    ->options(EnquiryStatus::class),

                TernaryFilter::make('safeguarding_flags')
                    ->label('Safeguarding')
                    ->placeholder('All enquiries')
                    ->trueLabel('With safeguarding flags')
                    ->falseLabel('No safeguarding flags'),

                Filter::make('occurred_at')
                    ->form([
                        DatePicker::make('occurred_from'),
                        DatePicker::make('occurred_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['occurred_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '>=', $date),
                            )
                            ->when(
                                $data['occurred_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['occurred_from'] ?? null) {
                            return "From {$data['occurred_from']}";
                        }

                        return null;
                    })
                    ->label('Date Range'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ConvertToServiceUserAction::make(),
                CloseEnquiryAction::make()
                    ->visible(fn ($record): bool => $record->status === EnquiryStatus::OPEN),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
