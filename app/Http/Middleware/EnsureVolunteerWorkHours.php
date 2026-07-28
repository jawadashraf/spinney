<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureVolunteerWorkHours
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $team = Filament::getTenant() ?? $user->currentTeam ?? $user->allTeams()->first();
        if ($team) {
            setPermissionsTeamId($team->getKey());
        }

        if ($user->isVolunteerLiaison() && ! $user->isWithinWorkHours()) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            Notification::make()
                ->title('Shift Ended')
                ->body('Access is restricted to your assigned work hours.')
                ->warning()
                ->send();

            return redirect()->to(Filament::getLoginUrl() ?? route('login'));
        }

        return $next($request);
    }
}
