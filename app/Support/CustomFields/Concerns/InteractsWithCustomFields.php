<?php

declare(strict_types=1);

namespace App\Support\CustomFields\Concerns;

use App\Support\CustomFields;
use Exception;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithCustomFields
{
    public function table(Table $table): Table
    {
        /** @var class-string $model */
        $model = $this instanceof RelationManager ? $this->getRelationship()->getModel()::class : $this->getModel();

        try {
            $table = static::getResource()::table($table);
        } catch (Exception) {
            $table = parent::table($table);
        }

        $columns = CustomFields::table()->forModel($model)->columns();
        $filters = CustomFields::table()->forModel($model)->filters();

        return $table->modifyQueryUsing(function (Builder $query): void {
            $query->with('customFieldValues.customField.options');
        })
            ->deferFilters(false)
            ->pushColumns($columns)
            ->pushFilters($filters);
    }
}
