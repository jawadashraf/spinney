<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use Filament\Actions\Exports\Exporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseExporter extends Exporter
{
    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query;
    }
}
