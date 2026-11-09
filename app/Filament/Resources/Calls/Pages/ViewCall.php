<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Calls\Actions\CancelCallAction;
use App\Filament\Resources\Calls\Actions\ReassignCallAction;
use App\Filament\Resources\Calls\Actions\RecordCallOutcomeAction;
use App\Filament\Resources\Calls\Actions\RescheduleCallAction;
use App\Filament\Resources\Calls\CallResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

final class ViewCall extends ViewRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('referralDirectory')
                ->label('Referral Directory')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->slideOver()
                ->modalHeading('Referral Directory Lookup')
                ->modalWidth(Width::SevenExtraLarge)
                ->modalContent(fn () => view('filament.resources.calls.referral-directory-slideover'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            RecordCallOutcomeAction::make(),
            RescheduleCallAction::make(),
            ReassignCallAction::make(),
            ActionGroup::make([
                EditAction::make(),
                CancelCallAction::make(),
            ]),
        ];
    }
}
