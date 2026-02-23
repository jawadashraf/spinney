<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use STS\FilamentImpersonate\Actions\Impersonate;

final class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()
                ->record($this->getRecord()),
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Flex::make([
                    TextEntry::make('name')
                        ->hiddenLabel()
                        ->size(TextSize::Large),
                    TextEntry::make('email')
                        ->label('Email')
                        ->icon('heroicon-o-envelope'),
                ]),
                Flex::make([
                    TextEntry::make('currentTeam.name')
                        ->label('Current Team'),
                    TextEntry::make('roles.name')
                        ->label('Roles')
                        ->badge(),
                ]),
                Flex::make([
                    TextEntry::make('email_verified_at')
                        ->label('Email Verified')
                        ->dateTime()
                        ->placeholder('Not verified'),
                    TextEntry::make('created_at')
                        ->label('Member Since')
                        ->dateTime(),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}
