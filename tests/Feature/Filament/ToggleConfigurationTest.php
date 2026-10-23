<?php

declare(strict_types=1);

use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ToggleColumn;

it('configures default onColor and offColor for form Toggle', function (): void {
    $toggle = Toggle::make('is_admin');

    expect($toggle->getOnColor())->toBe('success')
        ->and($toggle->getOffColor())->toBe('danger');
});

it('allows form Toggle to override default onColor and offColor', function (): void {
    $toggle = Toggle::make('is_admin')
        ->onColor('primary')
        ->offColor('gray');

    expect($toggle->getOnColor())->toBe('primary')
        ->and($toggle->getOffColor())->toBe('gray');
});

it('configures default onColor and offColor for table ToggleColumn', function (): void {
    $column = ToggleColumn::make('is_active');

    expect($column->getOnColor())->toBe('success')
        ->and($column->getOffColor())->toBe('danger');
});

it('allows table ToggleColumn to override default onColor and offColor', function (): void {
    $column = ToggleColumn::make('is_active')
        ->onColor('warning')
        ->offColor('gray');

    expect($column->getOnColor())->toBe('warning')
        ->and($column->getOffColor())->toBe('gray');
});
