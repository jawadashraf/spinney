<?php

declare(strict_types=1);

namespace App\Filament\Resources\Donations\RelationManagers;

use App\Filament\Resources\Donations\Schemas\DonationForm;
use App\Filament\Resources\Donations\Tables\DonationsTable;
use App\Models\Company;
use App\Models\Donation;
use App\Models\People;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Donation history on a person's or company's profile, shown only to managers and admins.
 */
final class DonationsRelationManager extends RelationManager
{
    protected static string $relationship = 'donations';

    protected static ?string $title = 'Donations';

    protected static ?string $modelLabel = 'donation';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-heart';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('viewAny', Donation::class) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(DonationForm::detailFields(fn (): People|Company => $this->ownerDonor()))
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(DonationsTable::columns(withDonor: false))
            ->defaultSort('donated_at', 'desc')
            ->filters([
                DonationsTable::dateFilter(),
                ...DonationsTable::sharedFilters(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Record donation')
                    ->icon('heroicon-o-plus')
                    ->size(Size::Small)
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'team_id' => $this->ownerDonor()->team_id]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    private function ownerDonor(): People|Company
    {
        /** @var People|Company */
        return $this->getOwnerRecord();
    }
}
