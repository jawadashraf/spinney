<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use AlizHarb\ActivityLog\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\UserResource\Actions\AccountStatusActions;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use STS\FilamentImpersonate\Actions\Impersonate;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $tenantOwnershipRelationshipName = 'teams';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('first_name')
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->unique(ignoreRecord: true),
            Callout::make('duplicate_user_warning')
                ->columnSpanFull()
                ->visible(fn (Get $get, ?Model $record): bool => self::detectUserDuplicate($get, $record) !== null)
                ->heading(fn (Get $get, ?Model $record): ?string => self::detectUserDuplicate($get, $record)['heading'] ?? null)
                ->color(fn (Get $get, ?Model $record): ?string => self::detectUserDuplicate($get, $record)['color'] ?? 'warning')
                ->description(fn (Get $get, ?Model $record): ?HtmlString => self::detectUserDuplicate($get, $record)['description'] ?? null)
                ->actions([
                    Action::make('resolveDuplicateUser')
                        ->label(fn (Get $get, ?Model $record): string => self::detectUserDuplicate($get, $record)['action_label'] ?? 'View Record')
                        ->url(fn (Get $get, ?Model $record): ?string => self::detectUserDuplicate($get, $record)['action_url'] ?? null)
                        ->visible(fn (Get $get, ?Model $record): bool => filled(self::detectUserDuplicate($get, $record)['action_url'] ?? null))
                        ->openUrlInNewTab()
                        ->color(fn (Get $get, ?Model $record): string => self::detectUserDuplicate($get, $record)['color'] ?? 'warning')
                        ->icon('heroicon-m-arrow-top-right-on-square'),
                ]),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->maxLength(255),
            DateTimePicker::make('email_verified_at')
                ->label('Email Verified At')
                ->default(now()),
            Select::make('roles')
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'name',
                    modifyQueryUsing: function ($query) {
                        $team = Filament::getTenant() ?? auth()->user()->currentTeam ?? auth()->user()->allTeams()->first();
                        if ($team) {
                            setPermissionsTeamId($team->getKey());
                        }

                        return $query;
                    }
                )
                ->multiple()
                ->preload()
                ->searchable()
                ->saveRelationshipsUsing(function (User $record, $state): void {
                    $team = Filament::getTenant() ?? $record->currentTeam ?? $record->allTeams()->first() ?? auth()->user()?->currentTeam;
                    if ($team) {
                        setPermissionsTeamId($team->getKey());
                    }

                    $record->syncRoles($state ?? []);
                }),
            Select::make('departments')
                ->relationship(
                    name: 'departments',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn ($query) => $query->where('departments.team_id', ($tenant = Filament::getTenant()) ? $tenant->getKey() : auth()->user()?->current_team_id)
                )
                ->multiple()
                ->preload()
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_photo_url')
                    ->label('')
                    ->imageSize(24)
                    ->circular(),
                TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->state(fn (User $record): string => $record->isDeactivated() ? 'Deactivated' : 'Active')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Deactivated' ? 'danger' : 'success'),
                TextColumn::make('deactivated_at')
                    ->label('Deactivated')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('currentTeam.name')
                    ->label('Current Team')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('departments.name')
                    ->label('Departments')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('status')
                    ->label('Status')
                    ->placeholder('All staff')
                    ->trueLabel('Active')
                    ->falseLabel('Deactivated')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('users.deactivated_at'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('users.deactivated_at'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Impersonate::make(),
                    AccountStatusActions::deactivate(),
                    AccountStatusActions::reactivate(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UserResource\RelationManagers\SchedulesRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return array{heading: string, color: string, description: HtmlString, action_label?: string|null, action_url?: string|null}|null
     */
    public static function detectUserDuplicate(Get|\Closure $get, ?Model $record): ?array
    {
        $email = rescue(fn () => $get('email'), rescue: false);

        if (blank($email)) {
            return null;
        }

        $email = mb_strtolower(mb_trim((string) $email));

        /** @var User|null $existingUser */
        $existingUser = User::query()
            ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $existingUser) {
            return null;
        }

        $name = e($existingUser->name);

        if ($existingUser->isDeactivated()) {
            $deactivatedAt = $existingUser->deactivated_at?->format('d M Y') ?? 'previously';
            $editUrl = rescue(fn () => self::getUrl('edit', [
                'tenant' => Filament::getTenant() ?? $existingUser->currentTeam ?? $existingUser->allTeams()->first() ?? auth()->user()?->currentTeam,
                'record' => $existingUser,
            ]));

            return [
                'heading' => 'Deactivated Staff Account Exists',
                'color' => 'warning',
                'description' => new HtmlString(
                    "A deactivated account already exists for <strong>{$name}</strong> (deactivated on {$deactivatedAt}). ".
                    'In accordance with staff policy, accounts are deactivated rather than deleted. '.
                    'You can reactivate this account instead of creating a new one.'
                ),
                'action_label' => 'View & Reactivate Account',
                'action_url' => $editUrl,
            ];
        }

        if ($existingUser->hasRole('service_user')) {
            return [
                'heading' => 'Email Belongs to a Service User',
                'color' => 'danger',
                'description' => new HtmlString(
                    "This email is registered to a Service User (<strong>{$name}</strong>). ".
                    'Staff accounts must use an organizational email address.'
                ),
                'action_label' => null,
                'action_url' => null,
            ];
        }

        $roles = $existingUser->roles->pluck('name')->implode(', ');
        $roleInfo = filled($roles) ? " (Roles: {$roles})" : '';
        $viewUrl = rescue(fn () => self::getUrl('view', [
            'tenant' => Filament::getTenant() ?? $existingUser->currentTeam ?? $existingUser->allTeams()->first() ?? auth()->user()?->currentTeam,
            'record' => $existingUser,
        ]));

        return [
            'heading' => 'Active Account Already Exists',
            'color' => 'danger',
            'description' => new HtmlString(
                "An active staff account already exists for <strong>{$name}</strong>{$roleInfo}. ".
                'Each staff member must have a unique email address.'
            ),
            'action_label' => 'View User Profile',
            'action_url' => $viewUrl,
        ];
    }
}
