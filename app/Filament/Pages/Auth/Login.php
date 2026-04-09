<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Resources\CompanyResource;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Auth;

final class Login extends \Filament\Auth\Pages\Login
{
    use HasCustomLayout;

    public function mount(): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->is_system_admin) {
                redirect('/sysadmin');

                return;
            }

            $tenant = $user->currentTeam ?? $user->allTeams()->first();

            if ($tenant) {
                 redirect(
                    route('filament.app.home', ['tenant' => $tenant])
                 );

                 return;
            }

            redirect('/');

            return;
        }

        $this->form->fill();
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->size(Size::Medium)
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }
}
