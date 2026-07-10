<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\CustomFields\OpportunityField as OpportunityCustomField;
use App\Filament\Resources\OpportunityResource\Forms\OpportunityForm;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Opportunity;
use App\Models\Team;
use App\Support\CustomFields;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Exception\InvalidArgumentException;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardFlex;
use Throwable;
use UnitEnum;

final class OpportunitiesBoard extends BoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Opportunities';

    protected static ?string $navigationParentItem = 'Opportunities';

    protected static string|null|UnitEnum $navigationGroup = 'Workspace';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-view-columns';

    /**
     * Configure the board using the new Filament V4 architecture.
     */
    public function board(Board $board): Board
    {
        return $board
            ->query(
                fn () => Opportunity::query()
                    ->leftJoin('custom_field_values as cfv', function (JoinClause $join): void {
                        $join->on('opportunities.id', '=', 'cfv.entity_id')
                            ->where('cfv.custom_field_id', '=', $this->stageCustomField()?->getKey());
                    })
                    ->select('opportunities.*', 'cfv.integer_value')
                    ->with(['company', 'contact'])
            )
            ->recordTitleAttribute('name')
            ->columnIdentifier('cfv.integer_value')
            ->positionIdentifier('order_column')
            ->searchable(['name'])
            ->columns($this->getColumns())
            ->cardSchema(function (Schema $schema): Schema {
                $descriptionCustomField = CustomFields::infolist()
                    ->forSchema($schema)
                    ->only(['description'])
                    ->hiddenLabels()
                    ->visibleWhenFilled()
                    ->withoutSections()
                    ->values()
                    ->first();

                $components = [];

                if ($descriptionCustomField) {
                    $components[] = $descriptionCustomField
                        ->columnSpanFull()
                        ->visible(filled(...))
                        ->formatStateUsing(fn (?string $state): string => str((string) $state)->stripTags()->limit()->toString());
                }

                $components[] = CardFlex::make([]);

                return $schema->components($components);
            })
            ->columnActions([
                CreateAction::make()
                    ->label('Add Opportunity')
                    ->icon('heroicon-o-plus')
                    ->iconButton()
                    ->modalWidth(Width::Large)
                    ->slideOver(false)
                    ->model(Opportunity::class)
                    ->schema(OpportunityForm::get(...))
                    ->using(function (array $data, array $arguments): Opportunity {
                        /** @var Team $currentTeam */
                        $currentTeam = Auth::guard('web')->user()->currentTeam;

                        /** @var Opportunity $opportunity */
                        $opportunity = $currentTeam->opportunities()->create($data);

                        $stageField = $this->stageCustomField();
                        $opportunity->saveCustomFieldValue($stageField, $arguments['column']);
                        $opportunity->order_column = $this->getBoardPositionInColumn((string) $arguments['column']);

                        return $opportunity;
                    }),
            ])
            ->cardActions([
                Action::make('edit')
                    ->label('Edit')
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(OpportunityForm::get(...))
                    ->fillForm(fn (Opportunity $record): array => [
                        'name' => $record->name,
                        'company_id' => $record->company_id,
                        'contact_id' => $record->contact_id,
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $record->update($data);
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Opportunity $record): void {
                        $record->delete();
                    }),
            ])
            ->filters([
                SelectFilter::make('companies')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->multiple(),
                SelectFilter::make('contacts')
                    ->label('Contact')
                    ->relationship('contact', 'name')
                    ->multiple(),
            ])
            ->filtersFormWidth(Width::Medium);
    }

    /**
     * Move card to new position using Rank-based positioning.
     *
     * @throws Throwable
     */
    public function moveCard(
        string $cardId,
        string $targetColumnId,
        ?string $afterCardId = null,
        ?string $beforeCardId = null
    ): void {
        $board = $this->getBoard();
        $query = $board->getQuery();

        if (! $query instanceof Builder) {
            throw new InvalidArgumentException('Board query not available');
        }

        /** @var Opportunity|null $card */
        $card = (clone $query)->find($cardId);
        if (! $card) {
            throw new InvalidArgumentException("Card not found: {$cardId}");
        }

        // Calculate new position using Rank service
        $newPosition = $this->calculatePositionBetweenCards($afterCardId, $beforeCardId, $targetColumnId);

        // Use transaction for data consistency
        DB::transaction(function () use ($card, $board, $targetColumnId, $newPosition): void {
            $columnIdentifier = $board->getColumnIdentifierAttribute();
            $columnValue = $this->resolveStatusValue($card, $columnIdentifier, $targetColumnId);
            $positionIdentifier = $board->getPositionIdentifierAttribute();

            /** @var Opportunity $card */
            $card->update([$positionIdentifier => $newPosition]);

            $card->saveCustomFieldValue($this->stageCustomField(), (string) $columnValue);
        });

        // Emit success event after successful transaction
        $this->dispatch('kanban-card-moved', [
            'cardId' => $cardId,
            'columnId' => $targetColumnId,
            'position' => $newPosition,
        ]);
    }

    /**
     * Get columns for the board.
     *
     * @return array<Column>
     *
     * @throws Exception
     */
    private function getColumns(): array
    {
        return $this->statuses()->map(fn (array $status): Column => Column::make((string) $status['id'])
            ->color($status['color'])
            ->label($status['name'])
        )->toArray();
    }

    private function stageCustomField(): ?CustomField
    {
        /** @var CustomField|null */
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', OpportunityCustomField::STAGE)
            ->first();
    }

    /**
     * @return Collection<int, array{id: mixed, custom_field_id: mixed, name: mixed, color: string}>
     */
    private function statuses(): Collection
    {
        $field = $this->stageCustomField();

        if (! $field instanceof CustomField) {
            return collect();
        }

        // Check if color options are enabled for this field
        /** @var array<string, mixed> $settings */
        $settings = (array) $field->settings;
        $colorsEnabled = $settings['enable_option_colors'] ?? false;

        return $field->options->map(function (CustomFieldOption $option) use ($colorsEnabled): array {
            /** @var array<string, mixed> $optionSettings */
            $optionSettings = (array) $option->settings;

            return [
                'id' => $option->getKey(),
                'custom_field_id' => $option->getAttribute('custom_field_id'),
                'name' => $option->getAttribute('name'),
                'color' => (string) ($colorsEnabled ? ($optionSettings['color'] ?? 'gray') : 'gray'),
            ];
        });
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
