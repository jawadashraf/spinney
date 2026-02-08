<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Tables;

use App\Enums\EnquiryCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->badge()
                    ->sortable(),

                TextColumn::make('people.name')
                    ->label('Caller')
                    ->searchable(),

                TextColumn::make('reason_for_contact')
                    ->limit(50)
                    ->searchable(),

                IconColumn::make('safeguarding_flags')
                    ->boolean()
                    ->label('Safeguarding'),

                TextColumn::make('user.name')
                    ->label('Staff')
                    ->sortable(),

                TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(EnquiryCategory::class),

                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Staff Member'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
