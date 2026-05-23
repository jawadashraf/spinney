<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Tables;

use App\Enums\EnquiryCallType;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryDirection;
use App\Enums\EnquirySourceType;
use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\Actions\AssignToDepartmentAction;
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
                TextColumn::make('direction')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('call_type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Unassigned')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Occurred')
                    ->toggleable(),

                TextColumn::make('due_date')
                    ->dateTime()
                    ->label('Due')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('source')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('outcome')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Logged')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(EnquiryCategory::class),

                SelectFilter::make('status')
                    ->options(EnquiryStatus::class),

                SelectFilter::make('direction')
                    ->options(EnquiryDirection::class),

                SelectFilter::make('call_type')
                    ->options(EnquiryCallType::class),

                SelectFilter::make('source')
                    ->options(EnquirySourceType::class),

                SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department'),

                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Staff Member'),

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
                    ->visible(fn ($record): bool => in_array($record->status, [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS], true)),
                AssignToDepartmentAction::make()
                    ->visible(fn ($record): bool => in_array($record->status, [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS], true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
