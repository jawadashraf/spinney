<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Resources\CompanyResource;
use Illuminate\Support\Facades\Auth;

final readonly class HomeController
{
    public function __invoke(): mixed
    {
        if (Auth::check()) {
            $user = Auth::user();

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

        return view('home.index2');
    }
}
