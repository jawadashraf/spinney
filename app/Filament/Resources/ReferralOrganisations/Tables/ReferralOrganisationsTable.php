<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralOrganisations\Tables;

use App\Enums\ReferralPriority;
use App\Models\ReferralOrganisation;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class ReferralOrganisationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority')
                    ->badge()
                    ->sortable(),

                TextColumn::make('area_of_support')
                    ->label('Support Area')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Organisation / Service')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('telephone')
                    ->label('Phone')
                    ->icon('heroicon-m-phone')
                    ->url(fn (ReferralOrganisation $record): ?string => filled($record->telephone) ? 'tel:'.preg_replace('/[^0-9+]/', '', (string) $record->telephone) : null)
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('website_url')
                    ->label('Web')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->formatStateUsing(fn (): string => 'Open link')
                    ->url(fn (ReferralOrganisation $record): ?string => $record->website_url, shouldOpenInNewTab: true)
                    ->placeholder('—'),

                TextColumn::make('how_to_refer')
                    ->label('How to Refer')
                    ->limit(60)
                    ->tooltip(fn (ReferralOrganisation $record): ?string => $record->how_to_refer),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('priority')
            ->filters([
                SelectFilter::make('area_of_support')
                    ->label('Area of Support')
                    ->options(fn (): array => ReferralOrganisation::query()
                        ->distinct()
                        ->orderBy('area_of_support')
                        ->pluck('area_of_support', 'area_of_support')
                        ->all())
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('priority')
                    ->options(ReferralPriority::class),

                TernaryFilter::make('is_active')
                    ->label('Active status')
                    ->default(true),

                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make()
                    ->slideOver(),
                EditAction::make()
                    ->slideOver(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->headerActions([
                Action::make('volunteerGuide')
                    ->label('Volunteer Referral Guide')
                    ->icon('heroicon-o-information-circle')
                    ->color('info')
                    ->slideOver()
                    ->modalHeading('Spinney Hill Drug Support — Volunteer Referral Guide')
                    ->modalWidth(Width::Large)
                    ->modalContent(fn () => view('filament.resources.referral-organisations.volunteer-guide-modal'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                CreateAction::make()
                    ->slideOver(),
            ]);
    }
}
