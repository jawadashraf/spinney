<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

    public function authenticate(): ?LoginResponse
    {
        $data = $this->data ?? [];
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if ($email && $password) {
            $credentials = ['email' => $email, 'password' => $password];
            /** @var User|null $user */
            $user = Auth::getProvider()->retrieveByCredentials($credentials);

            if ($user && Auth::getProvider()->validateCredentials($user, $credentials) && ($user->isVolunteerLiaison() && ! $user->isWithinWorkHours())) {
                throw ValidationException::withMessages([
                    'data.email' => __('Login Restricted: You can only log in during your assigned work hours.'),
                ]);
            }
        }

        return parent::authenticate();
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->size(Size::Medium)
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }
}
