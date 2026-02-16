<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\ApiTokens;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\EditProfile;
use App\Filament\Resources\CompanyResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Size;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Jetstream\Features;
use Openplain\FilamentShadcnTheme\Color;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use AlizHarb\ActivityLog\ActivityLogPlugin;
final class AppPanelProvider extends PanelProvider
{
    /**
     * Perform post-registration booting of components.
     */
    public function boot(): void
    {
        Action::configureUsing(fn (Action $action): Action => $action->size(Size::Small)->iconPosition('before'));
        Section::configureUsing(fn (Section $section): Section => $section->compact());
        Table::configureUsing(fn (Table $table): Table => $table);
    }

    /**
     * Configure the Filament admin panel.
     *
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('app')
            // ->domain('app.'.parse_url((string) config('app.url'))['host'])
            ->homeUrl(fn (): string => CompanyResource::getUrl('index'))
            ->brandName('Spinneyhill')
            ->plugins([
                FilamentShieldPlugin::make(),
                AuthUIEnhancerPlugin::make()
                ->showEmptyPanelOnMobile(true)
            ->formPanelPosition('right')
            ->formPanelWidth('40%')
            // ->emptyPanelBackgroundImageOpacity('100%')
            // ->emptyPanelBackgroundImageUrl('https://images.pexels.com/photos/466685/pexels-photo-466685.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2')
            ->emptyPanelBackgroundImageUrl(asset('images/spinney_bg.png')),
            ActivityLogPlugin::make()
                ->label('Log')
                ->pluralLabel('Logs')
                ->navigationGroup('System')
            ])
            ->login(Login::class)
            // ->registration(Register::class)
            ->passwordReset()
            ->emailVerification()
            ->databaseNotifications()
            ->brandLogoHeight('2.6rem')
            ->brandLogo(fn (): View|Factory => view('filament.app.logo'))
            ->viteTheme('resources/css/app.css')
            ->colors([
                // 'primary' => [
                //     50 => 'oklch(0.969 0.016 293.756)',
                //     100 => 'oklch(0.943 0.028 294.588)',
                //     200 => 'oklch(0.894 0.055 293.283)',
                //     300 => 'oklch(0.811 0.101 293.571)',
                //     400 => 'oklch(0.709 0.159 293.541)',
                //     500 => 'oklch(0.606 0.219 292.717)',
                //     600 => 'oklch(0.541 0.247 293.009)',
                //     700 => 'oklch(0.491 0.241 292.581)',
                //     800 => 'oklch(0.432 0.211 292.759)',
                //     900 => 'oklch(0.380 0.178 293.745)',
                //     950 => 'oklch(0.283 0.135 291.089)',
                //     'DEFAULT' => 'oklch(0.541 0.247 293.009)',
                // ],
                'primary' => Color::Default,
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->font('Inter')
            ->userMenuItems([
                Action::make('profile')
                    ->label('Profile')
                    ->icon('heroicon-m-user-circle')
                    ->url(fn (): string => $this->shouldRegisterMenuItem()
                        ? EditProfile::getUrl()
                        : url($panel->getPath())),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->pages([
                EditProfile::class,
                ApiTokens::class,
            ])
            ->spa()
            ->breadcrumbs(false)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Tasks')
                    ->icon('heroicon-o-shopping-cart'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => Blade::render('@env(\'local\')<x-login-link email="manuk.minasyan1@gmail.com" redirect-url="'.url('/').'" />@endenv'),
            )
            // ->renderHook(
            //     PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            //     fn (): View|Factory => view('filament.auth.social_login_buttons')
            // )
            // ->renderHook(
            //     PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE,
            //     fn (): View|Factory => view('filament.auth.social_login_buttons')
            // )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): View|Factory => view('filament.app.analytics')
            );

        if (Features::hasApiFeatures()) {
            $panel->userMenuItems([
                Action::make('api_tokens')
                    ->label('API Tokens')
                    ->icon('heroicon-o-key')
                    ->url(fn (): string => $this->shouldRegisterMenuItem()
                        ? ApiTokens::getUrl()
                        : url($panel->getPath())),
            ]);
        }

        return $panel;
    }

    public function shouldRegisterMenuItem(): bool
    {
        return Auth::user()?->hasVerifiedEmail() ?? false;
    }
}
