<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Widgets;

use App\Enums\CreationSource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Relaticle\SystemAdmin\Filament\Resources\CompanyResource;
use Relaticle\SystemAdmin\Filament\Resources\OpportunityResource;
use Relaticle\SystemAdmin\Filament\Resources\TaskResource;
use Relaticle\SystemAdmin\Filament\Resources\UserResource;
use Relaticle\SystemAdmin\Filament\Widgets\Concerns\HasCustomFieldQueries;

final class TeamPerformanceTableWidget extends BaseWidget
{
    use HasCustomFieldQueries;

    protected static ?string $heading = 'Team Performance Analytics';

    protected static ?int $sort = 4;

    /**
     * @return array<string, mixed>
     */
    public function getColumnSpan(): array
    {
        return [
            'default' => 'full',
            'md' => 'full',
            'lg' => 2,
            'xl' => 2,
            '2xl' => 2,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Team Member')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),

                Tables\Columns\TextColumn::make('tasks_created')
                    ->label('Tasks')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->url(fn (): string => TaskResource::getUrl('index')),

                Tables\Columns\TextColumn::make('tasks_completed')
                    ->label('Completed')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description('Feature temporarily disabled'),

                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('Success Rate')
                    ->formatStateUsing(fn (mixed $state): string => 'N/A')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description('Feature temporarily disabled'),

                Tables\Columns\TextColumn::make('opportunities_created')
                    ->label('Opportunities')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->url(fn (): string => OpportunityResource::getUrl('index')),

                Tables\Columns\TextColumn::make('companies_created')
                    ->label('Companies')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->url(fn (): string => CompanyResource::getUrl('index')),

                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->dateTime('M j, Y g:i A')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('tasks_created', 'desc')
            ->recordUrl(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                Action::make('view_all_users')
                    ->label('View all in Users')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (): string => UserResource::getUrl('index')),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->emptyStateHeading('No Team Activity')
            ->emptyStateDescription('User performance data will appear here once team members start creating tasks, opportunities, and companies')
            ->emptyStateIcon('heroicon-o-users');
    }

    /**
     * @return Builder<User>
     */
    protected function getTableQuery(): Builder
    {
        $systemSource = CreationSource::SYSTEM->value;
        $greatestFunc = DB::getDriverName() === 'sqlite' ? 'MAX' : 'GREATEST';

        return User::query()
            ->addSelect([
                'users.id',
                'users.name',
                'users.created_at',
                DB::raw("(SELECT COUNT(*) FROM tasks WHERE tasks.creator_id = users.id AND tasks.deleted_at IS NULL AND tasks.creation_source != '{$systemSource}') as tasks_created"),
                DB::raw('0 as tasks_completed'),
                DB::raw('0 as completion_rate'),
                DB::raw("(SELECT COUNT(*) FROM opportunities WHERE opportunities.creator_id = users.id AND opportunities.deleted_at IS NULL AND opportunities.creation_source != '{$systemSource}') as opportunities_created"),
                DB::raw("(SELECT COUNT(*) FROM companies WHERE companies.creator_id = users.id AND companies.deleted_at IS NULL AND companies.creation_source != '{$systemSource}') as companies_created"),
                DB::raw("{$greatestFunc}(
                    COALESCE((SELECT MAX(created_at) FROM tasks WHERE creator_id = users.id AND creation_source != '{$systemSource}'), '1970-01-01'),
                    COALESCE((SELECT MAX(created_at) FROM opportunities WHERE creator_id = users.id AND creation_source != '{$systemSource}'), '1970-01-01'),
                    COALESCE((SELECT MAX(created_at) FROM companies WHERE creator_id = users.id AND creation_source != '{$systemSource}'), '1970-01-01'),
                    COALESCE((SELECT MAX(created_at) FROM notes WHERE creator_id = users.id AND creation_source != '{$systemSource}'), '1970-01-01')
                ) as last_activity"),
            ])
            ->whereExists(function (QueryBuilder $query) use ($systemSource): void {
                $query->select(DB::raw(1))
                    ->from('tasks')
                    ->whereColumn('tasks.creator_id', 'users.id')
                    ->where('tasks.creation_source', '!=', $systemSource)
                    ->whereNull('tasks.deleted_at');
            })
            ->orWhereExists(function (QueryBuilder $query) use ($systemSource): void {
                $query->select(DB::raw(1))
                    ->from('opportunities')
                    ->whereColumn('opportunities.creator_id', 'users.id')
                    ->where('opportunities.creation_source', '!=', $systemSource)
                    ->whereNull('opportunities.deleted_at');
            })
            ->orWhereExists(function (QueryBuilder $query) use ($systemSource): void {
                $query->select(DB::raw(1))
                    ->from('companies')
                    ->whereColumn('companies.creator_id', 'users.id')
                    ->where('companies.creation_source', '!=', $systemSource)
                    ->whereNull('companies.deleted_at');
            });
    }
}
