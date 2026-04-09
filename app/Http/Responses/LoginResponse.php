<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Filament\Resources\CompanyResource;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

final readonly class LoginResponse implements \Filament\Auth\Http\Responses\Contracts\LoginResponse
{
    /** @phpstan-ignore-next-line return.unusedType */
    public function toResponse($request): RedirectResponse|Redirector // @pest-ignore-type
    {
        $panel = Filament::getCurrentPanel();

        // For system admin panel, use default Filament behavior
        if ($panel?->getId() === 'sysadmin') {
            return redirect($panel->getUrl());
        }

        // For app panel, redirect based on user role and tenants
        $user = $request->user('web');
        if ($user) {
            if ($user->is_system_admin) {
                return redirect('/sysadmin');
            }

            $currentTeam = $user->currentTeam ?? $user->allTeams()->first();

            if ($currentTeam) {
                return redirect(
                    route('filament.app.home', ['tenant' => $currentTeam])
                );
            }
        }

        return redirect(Filament::getUrl() ?? '/');
    }
}
