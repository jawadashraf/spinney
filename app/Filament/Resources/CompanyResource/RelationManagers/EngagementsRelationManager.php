<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Enums\CompanyEngagementType;
use App\Models\Company;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Visits, talks, events and meetings with a company in the outreach directory.
 */
final class EngagementsRelationManager extends RelationManager
{
    protected static string $relationship = 'engagements';

    protected static ?string $title = 'Engagement history';

    protected static ?string $modelLabel = 'engagement';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar-days';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(CompanyEngagementType::class)
                    ->required(),
                DatePicker::make('occurred_on')
                    ->label('Date')
                    ->default(now())
                    ->required(),
                Textarea::make('summary')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('summary')
            ->columns([
                TextColumn::make('occurred_on')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('summary')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Logged by')
                    ->placeholder('-'),
            ])
            ->defaultSort('occurred_on', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(CompanyEngagementType::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Log engagement')
                    ->icon('heroicon-o-plus')
                    ->size(Size::Small)
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'team_id' => $this->ownerCompany()->team_id]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    private function ownerCompany(): Company
    {
        /** @var Company */
        return $this->getOwnerRecord();
    }
}
