<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FaviconServiceProvider::class,
    App\Providers\Filament\AppPanelProvider::class,
    // App\Providers\Filament\KnowledgeBasePanelProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\JetstreamServiceProvider::class,
    App\Providers\MacroServiceProvider::class,
    Relaticle\Documentation\DocumentationServiceProvider::class,
    Relaticle\SystemAdmin\SystemAdminPanelProvider::class,
];
