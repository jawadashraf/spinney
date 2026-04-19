<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\TaskType;
use App\Enums\CustomFields\TaskField as TaskCustomField;
use App\Filament\Resources\TaskResource\Forms\TaskForm;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Task;
use App\Models\Team;
use App\Support\CustomFields;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;
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

final class TasksBoard extends BoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Tasks';

    protected static ?string $navigationParentItem = 'Tasks';

    protected static string|null|UnitEnum $navigationGroup = 'Workspace';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-view-columns';

    /**
     * Configure the board using the new Filament V4 architecture.
     */
    public function board(Board $board): Board
    {
        return $board
            ->query(function () {
                $query = Task::query()
                    ->leftJoin('custom_field_values as cfv', function (\Illuminate\Database\Query\JoinClause $join): void {
                        $join->on('tasks.id', '=', 'cfv.entity_id')
                            ->where('cfv.custom_field_id', '=', $this->statusCustomField()?->getKey());
                    })
                    ->select('tasks.*', 'cfv.integer_value');

                $user = auth()->user();

                if ($user->hasAnyRole(['super_admin', 'admin', 'manager'])) {
                    return $query;
                }

                $departmentIds = $user->departments()->pluck('departments.id');

                return $query->forDepartments($departmentIds);
            })
            ->recordTitleAttribute('title')
            ->columnIdentifier('cfv.integer_value')
            ->positionIdentifier('order_column')
            ->searchable(['title'])
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

                $components = [
                    TextEntry::make('type')
                        ->badge()
                        ->color(fn ($state) => TaskType::from($state)->getColor())
                        ->hiddenLabel(),
                ];

                if ($descriptionCustomField) {
                    $components[] = $descriptionCustomField
                        ->columnSpanFull()
                        ->visible(filled(...))
                        ->formatStateUsing(fn (?string $state): string => str((string) $state)->stripTags()->limit()->toString());
                }

                $components[] = CardFlex::make([]);
                $components[] = ImageEntry::make('assignees.profile_photo_url')
                    ->hiddenLabel()
                    ->alignLeft()
                    ->imageHeight(24)
                    ->circular()
                    ->visible(filled(...))
                    ->stacked();

                return $schema->components($components);
            })
            ->columnActions([
                CreateAction::make()
                    ->label('Add Task')
                    ->icon('heroicon-o-plus')
                    ->iconButton()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->slideOver(false)
                    ->model(Task::class)
                    ->schema(fn (Schema $schema): Schema => TaskForm::get($schema, ['status']))
                    ->using(function (array $data, array $arguments): Task {
                        /** @var Team $currentTeam */
                        $currentTeam = Auth::guard('web')->user()->currentTeam;

                        /** @var Task $task */
                        $task = $currentTeam->tasks()->create($data);

                        $statusField = $this->statusCustomField();
                        $task->saveCustomFieldValue($statusField, (string) $arguments['column']);
                        $task->order_column = $this->getBoardPositionInColumn((string) $arguments['column']);

                        return $task;
                    }),
            ])
            ->cardActions([
                Action::make('edit')
                    ->label('Edit')
                    ->slideOver()
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(fn (Schema $schema): Schema => TaskForm::get($schema))
                    ->fillForm(fn (Task $record): array => $record->toArray())
                    ->action(function (Task $record, array $data): void {
                        $record->update($data);
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Task $record): void {
                        $record->delete();
                    }),
            ])
            ->filters([
                SelectFilter::make('assignees')
                    ->label('Assignee')
                    ->relationship('assignees', 'name')
                    ->multiple(),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(TaskType::class)
                    ->attribute('type'),
                SelectFilter::make('department')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->visible(fn (): bool => auth()->user()->hasAnyRole(['super_admin', 'admin', 'manager'])),
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

        if (! $query instanceof \Illuminate\Database\Eloquent\Builder) {
            throw new InvalidArgumentException('Board query not available');
        }

        /** @var Task|null $card */
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

            /** @var Task $card */
            $card->update([$positionIdentifier => $newPosition]);

            $card->saveCustomFieldValue($this->statusCustomField(), (string) $columnValue);
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

    private function statusCustomField(): ?CustomField
    {
        /** @var CustomField|null */
        return CustomField::query()
            ->forEntity(Task::class)
            ->where('code', TaskCustomField::STATUS)
            ->first();
    }

    /**
     * @return Collection<int, array{id: mixed, custom_field_id: mixed, name: mixed, color: string}>
     */
    private function statuses(): Collection
    {
        $field = $this->statusCustomField();

        if (! $field instanceof CustomField) {
            return collect();
        }

        // Check if color options are enabled for this field
        $colorsEnabled = $field->settings->enable_option_colors ?? false;

        return $field->options->map(fn (CustomFieldOption $option): array => [
            'id' => $option->getKey(),
            'custom_field_id' => $option->getAttribute('custom_field_id'),
            'name' => $option->getAttribute('name'),
            'color' => $colorsEnabled ? ($option->settings->color ?? 'gray') : 'gray',
        ]);
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
