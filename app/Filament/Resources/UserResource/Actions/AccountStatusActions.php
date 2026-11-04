<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Actions;

use App\Actions\Users\DeactivateUser;
use App\Actions\Users\ReactivateUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Deactivate stops a staff member signing in without deleting them; Reactivate lets them back in.
 */
final class AccountStatusActions
{
    public static function deactivate(): Action
    {
        return Action::make('deactivate')
            ->label('Deactivate')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (User $record): bool => ! $record->isDeactivated() && self::authUser()?->canChangeAccountStatusOf($record) === true)
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => 'Deactivate '.$record->name.'?')
            ->modalDescription(fn (User $record): string => self::describeDeactivation($record))
            ->modalSubmitActionLabel('Deactivate')
            ->action(function (User $record): void {
                app(DeactivateUser::class)->handle($record);

                Notification::make()
                    ->title($record->name.' has been deactivated')
                    ->body('They can no longer sign in.')
                    ->success()
                    ->send();
            });
    }

    public static function reactivate(): Action
    {
        return Action::make('reactivate')
            ->label('Reactivate')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('success')
            ->visible(fn (User $record): bool => $record->isDeactivated() && self::authUser()?->canChangeAccountStatusOf($record) === true)
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => 'Reactivate '.$record->name.'?')
            ->modalDescription('They will be able to sign in again with their existing password and roles.')
            ->modalSubmitActionLabel('Reactivate')
            ->action(function (User $record): void {
                app(ReactivateUser::class)->handle($record);

                Notification::make()
                    ->title($record->name.' has been reactivated')
                    ->success()
                    ->send();
            });
    }

    private static function describeDeactivation(User $record): string
    {
        $description = 'They will be signed out straight away and will not be able to sign in. Their notes, calls and appointments stay on record, and you can reactivate them at any time.';

        $openCalls = $record->assignedCalls()->open()->count();

        if ($openCalls > 0) {
            $description .= sprintf(
                ' They still have %d open %s assigned: move %s to another liaison with Cover absence on the Calls page.',
                $openCalls,
                $openCalls === 1 ? 'call' : 'calls',
                $openCalls === 1 ? 'it' : 'them',
            );
        }

        return $description;
    }

    private static function authUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
