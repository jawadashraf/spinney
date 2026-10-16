<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutreachEmails;

use App\Filament\Resources\OutreachEmails\Pages\CreateOutreachEmail;
use App\Filament\Resources\OutreachEmails\Pages\EditOutreachEmail;
use App\Filament\Resources\OutreachEmails\Pages\ListOutreachEmails;
use App\Filament\Resources\OutreachEmails\Pages\ViewOutreachEmail;
use App\Filament\Resources\OutreachEmails\RelationManagers\RecipientsRelationManager;
use App\Filament\Resources\OutreachEmails\Schemas\OutreachEmailForm;
use App\Filament\Resources\OutreachEmails\Schemas\OutreachEmailInfolist;
use App\Filament\Resources\OutreachEmails\Tables\OutreachEmailsTable;
use App\Models\OutreachEmail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class OutreachEmailResource extends Resource
{
    protected static ?string $model = OutreachEmail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Outreach emails';

    protected static ?string $modelLabel = 'outreach email';

    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Schema $schema): Schema
    {
        return OutreachEmailForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OutreachEmailInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutreachEmailsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutreachEmails::route('/'),
            'create' => CreateOutreachEmail::route('/create'),
            'view' => ViewOutreachEmail::route('/{record}'),
            'edit' => EditOutreachEmail::route('/{record}/edit'),
        ];
    }
}
