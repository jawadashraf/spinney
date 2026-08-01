<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\RelationManagers;

use AlizHarb\ActivityLog\RelationManagers\ActivitiesRelationManager as BaseActivitiesRelationManager;
use App\Models\ServiceUser;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ServiceUserActivitiesRelationManager extends BaseActivitiesRelationManager
{
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(function (Builder $query) {
                /** @var ServiceUser $record */
                $record = $this->getOwnerRecord();

                if ($record->profile) {
                    $query->orWhere(function (Builder $subQuery) use ($record) {
                        $subQuery->where('subject_id', $record->profile->id)
                            ->where('subject_type', $record->profile->getMorphClass());
                    });
                }
            });
    }
}
