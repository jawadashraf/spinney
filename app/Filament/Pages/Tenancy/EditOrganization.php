<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;
use Override;

final class EditOrganization extends EditTenantProfile
{
    #[Override]
    public static function getLabel(): string
    {
        return 'Organization Profile';
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
