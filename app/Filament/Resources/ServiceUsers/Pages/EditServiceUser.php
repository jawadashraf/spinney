<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Pages;

use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\ServiceUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditServiceUser extends EditRecord
{
    protected static string $resource = ServiceUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ServiceUser $record */
        $oldEmail = $record->email;
        $record = parent::handleRecordUpdate($record, $data);

        // Sync email to linked User account if it changed
        if ($record->user && $record->email !== $oldEmail) {
            $record->user->update([
                'email' => $record->email,
            ]);
        }

        return $record;
    }
}
