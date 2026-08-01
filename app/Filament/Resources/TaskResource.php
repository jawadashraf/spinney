<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CreationSource;
use App\Enums\TaskType;
use App\Filament\Resources\TaskResource\Forms\TaskForm;
use App\Filament\Resources\TaskResource\Pages\ManageTasks;
use App\Filament\Resources\Tasks\Actions\RecordOutcomeAction;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskPriorityChangedNotification;
use App\Notifications\TaskUnassignedNotification;
use App\Support\CustomFields\ValueResolver;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;

final class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationLabel = 'Tasks';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    public static function form(Schema $schema): Schema
    {
        return TaskForm::get($schema);
    }

    public static function table(Table $table): Table
    {
        /** @var SupportCollection<string, CustomField> $customFields */
        $customFields = CustomField::query()->whereIn('code', ['status', 'priority'])->get()->keyBy('code');
        /** @var ValueResolver $valueResolver */
        $valueResolver = app(ValueResolver::class);

        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap()
                    ->limit(50)
                    ->weight('medium'),
                TextColumn::make('type')
                    ->badge()
                    ->label('Type')
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->placeholder('—'),
                TextColumn::make('assignees.name')
                    ->label('Assignee')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Task $record): string => $record->createdBy)
                    ->color(fn (Task $record): string => $record->isSystemCreated() ? 'secondary' : 'primary'),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->paginated([10, 25, 50])
            ->filters([
                Filter::make('assigned_to_me')
                    ->label('Assigned to me')
                    ->query(fn (Builder $query): Builder => $query->whereHas('assignees', function (Builder $query): void {
                        $query->where('users.id', Auth::id());
                    }))
                    ->toggle(),
                SelectFilter::make('assignees')
                    ->multiple()
                    ->relationship('assignees', 'name', fn (Builder $query) => $query->whereHas('roles', fn (Builder $query) => $query->whereIn('name', ['liaison', 'volunteer_liaison', 'manager', 'admin'])))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('creation_source')
                    ->label('Creation Source')
                    ->options(CreationSource::class)
                    ->multiple(),
                TrashedFilter::make(),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(TaskType::class)
                    ->multiple(),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => auth()->user()->hasAnyRole(['super_admin', 'admin', 'manager'])),
                Filter::make('due_this_week')
                    ->label('Due this week')
                    ->toggle()
                    ->default(true)
                    ->query(fn (Builder $query): Builder => $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->groups(array_filter([
                ...collect(['status', 'priority'])->map(fn (string $fieldCode): ?\Filament\Tables\Grouping\Group => $customFields->contains('code', $fieldCode) ? self::makeCustomFieldGroup($fieldCode, $customFields, $valueResolver) : null
                )->filter()->toArray(),
            ]))
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->before(function (Task $record) {
                            $record->old_assignees = $record->assignees->pluck('id')->toArray();

                            $customFields = CustomField::where('entity_type', Task::class)->get();
                            $priorityField = $customFields->firstWhere('code', 'priority');
                            $record->old_priority = $priorityField ? $record->getCustomFieldValue($priorityField) : null;
                        })
                        ->after(function (Task $record) {
                            $oldAssignees = $record->old_assignees ?? [];
                            $newAssignees = $record->assignees()->pluck('users.id')->toArray();

                            $added = array_diff($newAssignees, $oldAssignees);
                            $removed = array_diff($oldAssignees, $newAssignees);

                            foreach ($added as $userId) {
                                $user = User::find($userId);
                                if ($user instanceof User) {
                                    $user->notify(new TaskAssignedNotification($record));
                                }
                            }

                            foreach ($removed as $userId) {
                                $user = User::find($userId);
                                if ($user instanceof User) {
                                    $user->notify(new TaskUnassignedNotification($record));
                                }
                            }

                            $customFields = CustomField::where('entity_type', Task::class)->get();
                            $priorityField = $customFields->firstWhere('code', 'priority');
                            if ($priorityField) {
                                $newPriority = $record->getCustomFieldValue($priorityField);

                                if ($newPriority !== $record->old_priority) {
                                    $option = is_scalar($newPriority) ? CustomFieldOption::find($newPriority) : null;
                                    if ($option instanceof CustomFieldOption && strtolower($option->name) === 'urgent') {
                                        $notifiables = $record->assignees->push($record->creator)->filter()->unique('id');
                                        foreach ($notifiables as $user) {
                                            $user->notify(new TaskPriorityChangedNotification($record));
                                        }
                                    }
                                }
                            }
                        }),
                    RestoreAction::make(),
                    RecordOutcomeAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTasks::route('/'),
        ];
    }

    /**
     * @param  SupportCollection<string, CustomField>  $customFields
     */
    private static function makeCustomFieldGroup(string $fieldCode, SupportCollection $customFields, ValueResolver $valueResolver): Group
    {
        $field = $customFields[$fieldCode];
        $label = ucfirst($fieldCode);

        return Group::make("{$fieldCode}_group")
            ->label($label)
            ->orderQueryUsing(fn (Builder $query, string $direction): Builder => $query->orderBy(
                $field->values()
                    ->select($field->getValueColumn())
                    ->whereColumn('custom_field_values.entity_id', 'tasks.id')
                    ->limit(1)
                    ->getQuery(),
                /** @phpstan-ignore argument.type */
                $direction
            ))
            ->getTitleFromRecordUsing(function (Task $record) use ($valueResolver, $field, $label): string {
                $value = $valueResolver->resolve($record, $field);

                return empty($value) ? "No {$label}" : $value;
            })
            ->getKeyFromRecordUsing(function (Task $record) use ($field): string {
                $fieldValue = $record->customFieldValues->firstWhere('custom_field_id', $field->id);
                $rawValue = $fieldValue?->getValue();

                return $rawValue ? (string) $rawValue : '0';
            })
            ->scopeQueryByKeyUsing(function (Builder $query, string $key) use ($field): Builder {
                if ($key === '0') {
                    return $query->whereDoesntHave('customFieldValues', function (Builder $query) use ($field): void {
                        $query->where('custom_field_id', $field->id);
                    });
                }

                return $query->whereHas('customFieldValues', function (Builder $query) use ($field, $key): void {
                    $query->where('custom_field_id', $field->id)
                        ->where($field->getValueColumn(), $key);
                });
            });
    }

    /**
     * @return Builder<Task>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Task> $query */
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('0=1');
        }

        if ($user->hasAnyRole(['super_admin', 'admin', 'manager'])) {
            return $query;
        }

        $departmentIds = $user->departments()->pluck('departments.id');

        return $query->forDepartments($departmentIds);
    }
}
