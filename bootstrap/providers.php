<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\FaviconServiceProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\JetstreamServiceProvider;
use App\Providers\MacroServiceProvider;
use Relaticle\Documentation\DocumentationServiceProvider;
use Relaticle\SystemAdmin\SystemAdminPanelProvider;

return [
    AppServiceProvider::class,
    FaviconServiceProvider::class,
    Relaticle\SystemAdmin\SystemAdminPanelProvider::class,
    AppPanelProvider::class,
    // App\Providers\Filament\KnowledgeBasePanelProvider::class, // TODO: Re-enable when guava/filament-knowledge-base supports Laravel 13
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    JetstreamServiceProvider::class,
    MacroServiceProvider::class,
    DocumentationServiceProvider::class,
];
